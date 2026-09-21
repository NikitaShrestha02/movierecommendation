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

/**
 * A candidate counts as "different" when its cosine to the seed is
 * below this. Tuned against the TMDB data: above roughly 0.15 films
 * start sharing a genre cluster with the seed.
 */
const DIFFERENT_MAX_SIM = 0.12;

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
// 2. DIFFERENT -- personalised, but unlike the seed
// ---------------------------------------------------------------
$pool = knn_recommend($model, $watched, [
    'preferredGenres' => $prefGenres,
    'limit'           => 120,
]);

$seedGenreSet = array_flip($seedGenres);
$different    = [];

foreach ($pool as $rec) {
    $cid = $rec['id'];
    if (isset($watchedSet[$cid]) || isset($similarIds[$cid])) {
        continue;
    }

    // Must not be close to the seed in feature space
    if (knn_cosine($seedVec, $vectors[$cid]) >= DIFFERENT_MAX_SIM) {
        continue;
    }

    // ...and must not share a genre with it
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

    $different[$cid] = $rec['score'];
    if (count($different) >= DIFFERENT_COUNT) {
        break;
    }
}

// ---------------------------------------------------------------
// Hydrate
// ---------------------------------------------------------------
$allIds = array_merge([$movieId], array_keys($similarIds), array_keys($different));
$place  = implode(',', array_fill(0, count($allIds), '?'));

$stmt = $conn->prepare(
    "SELECT id, original_title, poster_path, genres, release_date FROM movies WHERE id IN ($place)"
);
$stmt->bind_param(str_repeat('i', count($allIds)), ...$allIds);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[(int) $r['id']] = $r;
}
$stmt->close();

/** Build one card, explaining the shared ground with the seed. */
function sim_card(array $rows, array $genres, int $id, float $score, array $seedGenres, string $kind): ?array
{
    if (!isset($rows[$id])) {
        return null;
    }
    $r  = $rows[$id];
    $g  = $genres[$id] ?? [];

    if ($kind === 'similar') {
        $shared = array_values(array_intersect($g, $seedGenres));
        $reason = $shared
            ? 'Also ' . implode(' & ', array_map('ucwords', array_slice($shared, 0, 2)))
            : 'Similar themes and keywords';
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
        'match'    => (int) round(100 * min(1.0, $score)),
        'reason'   => $reason,
    ];
}

$similarOut = [];
foreach ($similarIds as $id => $score) {
    if ($card = sim_card($rows, $genres, $id, $score, $seedGenres, 'similar')) {
        $similarOut[] = $card;
    }
}

$differentOut = [];
$maxDiff = $different ? max($different) : 1.0;
foreach ($different as $id => $score) {
    if ($card = sim_card($rows, $genres, $id, $score / ($maxDiff ?: 1), $seedGenres, 'different')) {
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