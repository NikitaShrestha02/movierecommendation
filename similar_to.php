<?php
/**
 * similar_to.php
 * ---------------------------------------------------------------
 * Powers the "you just watched X" popup.
 *
 *   GET similar_to.php?movie_id=123
 *
 * Returns three things:
 *   seed      - the film they just marked as watched
 *   similar   - its nearest neighbours (more of the same)
 *   different - films that fit their overall taste but are
 *               deliberately UNLIKE the seed (a change of pace)
 *
 * "different" is not random. It takes the user's normal KNN
 * recommendations and keeps only those with low similarity to the
 * seed and no genre overlap with it, so the suggestion is still
 * personalised -- just not more of what they have right now.
 * ---------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function sim_json($payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['uemail'])) {
    sim_json(['similar' => [], 'different' => []]);
}

$movieId = isset($_GET['movie_id']) ? (int) $_GET['movie_id'] : 0;
if ($movieId <= 0) {
    sim_json(['error' => 'Missing movie_id'], 400);
}

require_once __DIR__ . '/knn_lib.php';
include __DIR__ . '/connection.php';

if (!isset($conn) || $conn->connect_error) {
    error_log('similar_to: DB connection failed');
    sim_json(['error' => 'Service unavailable'], 503);
}
$conn->set_charset('utf8mb4');

// How many of each to show
const SIMILAR_COUNT   = 4;
const DIFFERENT_COUNT = 3;

// ---------------------------------------------------------------
// User + watched list
// ---------------------------------------------------------------
$stmt = $conn->prepare("SELECT id, preferred_genres FROM user WHERE email = ?");
$stmt->bind_param('s', $_SESSION['uemail']);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userRow) {
    sim_json(['similar' => [], 'different' => []]);
}

$userId     = (int) $userRow['id'];
$prefGenres = knn_split_names($userRow['preferred_genres'] ?? '');

$watched    = [];
$watchedIds = [];
$seedRating = null;

$stmt = $conn->prepare("SELECT movies_id, rating FROM watched_movies WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $id = (int) $row['movies_id'];
    $watched[]    = ['id' => $id, 'rating' => $row['rating']];
    $watchedIds[] = $id;
    if ($id === $movieId) {
        $seedRating = $row['rating'] !== null ? (int) $row['rating'] : null;
    }
}
$stmt->close();

// ---------------------------------------------------------------
// Model
// ---------------------------------------------------------------
try {
    $model = knn_build_model($conn);
} catch (Throwable $e) {
    error_log('similar_to: model build failed: ' . $e->getMessage());
    sim_json(['error' => 'Could not build model'], 500);
}

$vectors = $model['vectors'];
$genres  = $model['genres'];

if (!isset($vectors[$movieId])) {
    // Film has no usable genres/keywords -- nothing to compare against
    sim_json(['similar' => [], 'different' => [], 'seed' => ['movie_id' => $movieId]]);
}

$seedVec    = $vectors[$movieId];
$seedGenres = $genres[$movieId] ?? [];
$watchedSet = array_flip($watchedIds);

// ---------------------------------------------------------------
// 1. SIMILAR -- nearest neighbours of the seed itself
// ---------------------------------------------------------------
$sims = [];
foreach ($vectors as $cid => $vec) {
    if ($cid === $movieId || isset($watchedSet[$cid])) {
        continue;
    }
    $s = knn_cosine($seedVec, $vec);
    if ($s >= KNN_MIN_SIM) {
        // Damp films we barely have metadata for, same as the main recommender
        $sims[$cid] = $s * knn_confidence(count($vec));
    }
}
arsort($sims);
$similarIds = array_slice($sims, 0, SIMILAR_COUNT, true);

// ---------------------------------------------------------------
// 2. DIFFERENT -- personalised, but a change of pace from the seed.
//
// The previous version filtered knn_recommend()'s candidate pool. That
// pool is generated only from films sharing tokens with what the user
// has already watched, so by construction it is full of films *like*
// the seed and almost never held anything unlike it -- the "Fancy
// something different" section therefore came back empty. (That is the
// bug being fixed.)
//
// Instead we score the whole catalogue for "different-ness": a film
// qualifies when it shares NO genre with the seed, and is then ranked
// by how well it still fits the rest of the user's taste (their other
// watched films and preferred genres), lightly penalising any residual
// resemblance to the seed. This keeps the suggestions personal while
// guaranteeing the section fills whenever off-genre films exist.
// ---------------------------------------------------------------
$seedGenreSet = array_flip($seedGenres);

// Taste profile = the user's positively-weighted watched films OTHER
// than the seed, plus a vector built from their preferred genres.
$tasteWatched = [];                       // [watched_id => rating weight]
foreach ($watched as $w) {
    $wid = (int) $w['id'];
    if ($wid === $movieId || !isset($vectors[$wid])) {
        continue;
    }
    $wt = knn_rating_weight($w['rating'] ?? null);
    if ($wt > 0) {
        $tasteWatched[$wid] = $wt;
    }
}

$profileVec = [];
if ($prefGenres) {
    $ptokens = [];
    foreach ($prefGenres as $n) {
        foreach (knn_tokens_from_name(knn_normalise($n)) as $t) {
            $ptokens[] = $t;
        }
    }
    $profileVec = knn_vector($ptokens, $model['idf']);
}

$diffScores = [];
foreach ($vectors as $cid => $vec) {
    if ($cid === $movieId || isset($watchedSet[$cid]) || isset($similarIds[$cid])) {
        continue;
    }

    // A change of pace means sharing no genre with the seed.
    $overlap = false;
    foreach (($genres[$cid] ?? []) as $g) {
        if (isset($seedGenreSet[$g])) {
            $overlap = true;
            break;
        }
    }
    if ($overlap) {
        continue;
    }

    $seedAff = knn_cosine($seedVec, $vec);   // residual resemblance to the seed

    // How well the film still fits the rest of the user's taste.
    $taste = 0.0;
    foreach ($tasteWatched as $wid => $wt) {
        $s = knn_cosine($vectors[$wid], $vec);
        if ($s > $taste) {
            $taste = $s;                     // nearest non-seed watched film
        }
    }
    if ($profileVec) {
        $taste += 0.5 * knn_cosine($profileVec, $vec);
    }

    $confidence = knn_confidence($model['nnz'][$cid] ?? count($vec));

    // Reward taste fit; gently push down anything still seed-like.
    $diffScores[$cid] = ($taste - 0.25 * $seedAff) * $confidence;
}

arsort($diffScores);
$different = array_slice($diffScores, 0, DIFFERENT_COUNT, true);

// ---------------------------------------------------------------
// Hydrate
// ---------------------------------------------------------------
$allIds = array_merge([$movieId], array_keys($similarIds), array_keys($different));
$place  = implode(',', array_fill(0, count($allIds), '?'));

$stmt = $conn->prepare(
    "SELECT id, original_title, poster_path, genres, keywords, overview, release_date FROM movies WHERE id IN ($place)"
);
$stmt->bind_param(str_repeat('i', count($allIds)), ...$allIds);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[(int) $r['id']] = $r;
}
$stmt->close();

// The seed's own keywords -- used to name the plot themes a "similar" pick
// actually shares with it.
$seedKeywords = knn_split_names($rows[$movieId]['keywords'] ?? '');

/** Trim an overview to a short snippet ending on a word boundary. */
function sim_snippet(?string $text, int $limit = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));
    if ($text === '' || strlen($text) <= $limit) {
        return $text;
    }
    $cut = substr($text, 0, $limit);
    $sp  = strrpos($cut, ' ');
    if ($sp !== false && $sp > 40) {
        $cut = substr($cut, 0, $sp);
    }
    return rtrim($cut, " .,;:") . '…';
}

/** Build one card, explaining the shared ground with the seed. */
function sim_card(array $rows, array $genres, int $id, float $score, array $seedGenres, array $seedKeywords, string $kind): ?array
{
    if (!isset($rows[$id])) {
        return null;
    }
    $r  = $rows[$id];
    $g  = $genres[$id] ?? [];
    $kw = knn_split_names($r['keywords'] ?? '');

    $themes = [];

    if ($kind === 'similar') {
        $shared = array_values(array_intersect($g, $seedGenres));
        // Plot themes (keywords) this film shares with the seed.
        $themes = array_map('ucwords', array_slice(array_values(array_intersect($kw, $seedKeywords)), 0, 3));
        $reason = $shared
            ? 'Also ' . implode(' & ', array_map('ucwords', array_slice($shared, 0, 2)))
            : ($themes ? 'Shares plot themes with it' : 'Similar themes and keywords');
    } else {
        $reason = $g
            ? 'A change of pace: ' . ucwords($g[0])
            : 'Something different';
    }

    return [
        'movie_id' => $id,
        'title'    => $r['original_title'],
        'poster'   => !empty($r['poster_path']) ? $r['poster_path'] : 'default.jpg',
        'year'     => !empty($r['release_date']) ? substr($r['release_date'], 0, 4) : '',
        'genres'   => implode(', ', array_map('ucwords', $g)),
        'match'    => max(0, min(100, (int) round(100 * min(1.0, $score)))),
        'reason'   => $reason,
        'themes'   => $themes,
        'overview' => sim_snippet($r['overview'] ?? '', 150),
    ];
}

$similarOut = [];
foreach ($similarIds as $id => $score) {
    if ($card = sim_card($rows, $genres, $id, $score, $seedGenres, $seedKeywords, 'similar')) {
        $similarOut[] = $card;
    }
}

$differentOut = [];
$maxDiff = $different ? max($different) : 1.0;
foreach ($different as $id => $score) {
    if ($card = sim_card($rows, $genres, $id, $score / ($maxDiff ?: 1), $seedGenres, $seedKeywords, 'different')) {
        $differentOut[] = $card;
    }
}

$conn->close();

sim_json([
    'seed' => [
        'movie_id' => $movieId,
        'title'    => $rows[$movieId]['original_title'] ?? '',
        'genres'   => implode(', ', array_map('ucwords', $seedGenres)),
        'rating'   => $seedRating,
    ],
    'watched_count' => count($watchedIds),
    'similar'       => $similarOut,
    'different'     => $differentOut,
]);