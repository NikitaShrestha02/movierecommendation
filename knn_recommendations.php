<?php
/**
 * knn_recommendations.php
 * ---------------------------------------------------------------
 * JSON endpoint returning personalised recommendations.
 *
 *   GET knn_recommendations.php              -> profile-based
 *   GET knn_recommendations.php?mood=Happy   -> filtered by mood
 *   GET knn_recommendations.php?mood=all     -> clears the mood
 *
 * Algorithm: item-based KNN over TF-IDF vectors of genres+keywords.
 * For each candidate we find the K watched films most similar to it
 * and sum their rating weights times similarity. See knn_lib.php.
 * ---------------------------------------------------------------
 */

// Log errors instead of hiding them. Warnings would corrupt the JSON
// body, so display is off -- but they still reach the error log, which
// is how you find out a column is missing instead of silently getting
// zeros forever.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/** Always emit valid JSON, even on failure. */
function knn_json($payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------
// Authentication
//
// NOTE: the previous version fell back to $_COOKIE['uemail'] when the
// session was missing. A cookie is client-controlled, so anyone could
// set it to another user's email and read that user's taste profile.
// The session is the only thing trusted here.
// ---------------------------------------------------------------
if (empty($_SESSION['uemail'])) {
    knn_json([]);
}

require_once __DIR__ . '/knn_lib.php';
include __DIR__ . '/connection.php';

if (!isset($conn) || $conn->connect_error) {
    error_log('KNN: DB connection failed');
    knn_json(['error' => 'Service unavailable'], 503);
}
$conn->set_charset('utf8mb4');

// ---------------------------------------------------------------
// Mood handling
// ---------------------------------------------------------------
$moodMap = [
    'happy'       => ['comedy', 'animation', 'family', 'music'],
    'relaxed'     => ['animation', 'family', 'music', 'comedy', 'tv movie'],
    'sad'         => ['drama', 'romance'],
    'excited'     => ['action', 'thriller', 'adventure', 'science fiction', 'mystery'],
    'romantic'    => ['romance', 'drama', 'comedy'],
    'adventurous' => ['adventure', 'action', 'fantasy', 'science fiction'],
    'thoughtful'  => ['mystery', 'documentary', 'history', 'drama', 'science fiction'],
    'chill'       => ['comedy', 'animation', 'family', 'fantasy'],
];

$moodParam = isset($_GET['mood'])
    ? strtolower(trim($_GET['mood']))
    : strtolower(trim($_SESSION['user_mood'] ?? ''));

$activeMoodKey = '';
$moodGenres    = [];

if ($moodParam === 'all' || $moodParam === 'reset') {
    unset($_SESSION['user_mood']);
} elseif (isset($moodMap[$moodParam])) {
    $activeMoodKey           = $moodParam;
    $moodGenres              = $moodMap[$moodParam];
    $_SESSION['user_mood']   = $moodParam;
}

// ---------------------------------------------------------------
// User profile
// ---------------------------------------------------------------
$stmt = $conn->prepare("SELECT id, preferred_genres FROM user WHERE email = ?");
$stmt->bind_param('s', $_SESSION['uemail']);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userRow) {
    knn_json([]);
}

$userId     = (int) $userRow['id'];
$prefGenres = knn_split_names($userRow['preferred_genres'] ?? '');

// ---------------------------------------------------------------
// Watched films with their star ratings
// ---------------------------------------------------------------
$watched         = [];
$watchedTitle    = [];
$watchedGenres   = [];   // id => [genre names]
$watchedKeywords = [];   // id => [keyword names]

$stmt = $conn->prepare(
    "SELECT m.id, m.original_title, m.genres, m.keywords, wm.rating
       FROM watched_movies wm
       JOIN movies m ON wm.movies_id = m.id
      WHERE wm.user_id = ?"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();

$highRatedGenres = [];
$watchlistGenres = [];

while ($row = $res->fetch_assoc()) {
    $id = (int) $row['id'];
    $watched[] = ['id' => $id, 'rating' => $row['rating']];
    $watchedTitle[$id] = $row['original_title'];

    $g = knn_split_names($row['genres']);
    $watchedGenres[$id]   = $g;
    $watchedKeywords[$id] = knn_split_names($row['keywords'] ?? '');

    foreach ($g as $gn) {
        $watchlistGenres[$gn] = true;
        if ((float) $row['rating'] >= 4.0) {
            $highRatedGenres[$gn] = true;
        }
    }
}
$stmt->close();

// ---------------------------------------------------------------
// Score
// ---------------------------------------------------------------
try {
    $model = knn_build_model($conn);
} catch (Throwable $e) {
    error_log('KNN model build failed: ' . $e->getMessage());
    knn_json(['error' => 'Could not build recommendation model'], 500);
}

if (empty($model['vectors'])) {
    knn_json([]);
}

// ---------------------------------------------------------------
// Two-tier output.
//
// TOP_PICKS are the highest scoring films, always in the same order:
// these are the algorithm's actual answer and must not move around.
//
// EXTRA_PICKS are sampled from the rest of a wider pool. Films ranked
// 6th and 30th usually differ by a small margin, so any of them is a
// defensible suggestion -- rotating them keeps the page fresh without
// degrading the recommendation.
//
// The shuffle is seeded with the user id and today's date, so the list
// is stable all day for that user and changes tomorrow. Reloading the
// page does not reshuffle, which would look broken.
// ---------------------------------------------------------------
const TOP_PICKS   = 5;
const EXTRA_PICKS = 5;
const POOL_SIZE   = 30;

$ranked = knn_recommend($model, $watched, [
    'moodGenres'      => $moodGenres,
    'preferredGenres' => $prefGenres,
    'limit'           => POOL_SIZE,
]);

$top  = array_slice($ranked, 0, TOP_PICKS);
$rest = array_slice($ranked, TOP_PICKS);

if ($rest) {
    // Seeded so the rotation is deterministic per user per day.
    mt_srand(crc32($userId . '|' . $activeMoodKey . '|' . date('Y-m-d')));

    // Fisher-Yates using the seeded generator. shuffle() ignores the
    // seed on PHP 7.1+, so it cannot be used here.
    for ($i = count($rest) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        [$rest[$i], $rest[$j]] = [$rest[$j], $rest[$i]];
    }

    // Put the sampled extras back in score order so the row still
    // reads best-first rather than looking arbitrary.
    $rest = array_slice($rest, 0, EXTRA_PICKS);
    usort($rest, fn($a, $b) => ($b['score'] <=> $a['score']) ?: ($a['id'] <=> $b['id']));
}

// Tag each result so the front end can render two sections
foreach ($top as $i => $_) {
    $top[$i]['section'] = 'top';
}
foreach ($rest as $i => $_) {
    $rest[$i]['section'] = 'more';
}

$ranked = array_merge($top, $rest);

// ---------------------------------------------------------------
// Nepali cinema row
//
// The bulk catalogue is English-language (TMDB), so Nepali films
// rarely win the main content ranking. This adds a guaranteed row of
// Nepali films (original_language = 'ne', or tagged with a Nepali
// genre), still ranked by the user's taste via knn_score_subset, so
// regional cinema is always represented.
// ---------------------------------------------------------------
const NEPALI_PICKS = 6;

$mainIds  = array_flip(array_column($ranked, 'id'));
$watchedIdSet = array_flip(array_column($watched, 'id'));

$neIds = [];
$neRes = $conn->query(
    "SELECT id FROM movies WHERE original_language = 'ne' OR genres LIKE '%Nepali%'"
);
if ($neRes) {
    while ($r = $neRes->fetch_assoc()) {
        $id = (int) $r['id'];
        // Skip films already watched or already shown in the main rows.
        if (!isset($watchedIdSet[$id]) && !isset($mainIds[$id])) {
            $neIds[] = $id;
        }
    }
}

if ($neIds) {
    $nepali = knn_score_subset($model, $watched, $neIds, [
        'moodGenres'      => $moodGenres,
        'preferredGenres' => $prefGenres,
        'limit'           => NEPALI_PICKS,
    ]);
    foreach ($nepali as $i => $_) {
        $nepali[$i]['section'] = 'nepali';
    }
    $ranked = array_merge($ranked, $nepali);
}

// Nothing personalised and no Nepali films to show either.
if (!$ranked) {
    knn_json([]);
}

// ---------------------------------------------------------------
// Hydrate with display data
// ---------------------------------------------------------------
$ids   = array_column($ranked, 'id');
$place = implode(',', array_fill(0, count($ids), '?'));

$stmt = $conn->prepare(
    "SELECT id, original_title, poster_path, genres, keywords, overview FROM movies WHERE id IN ($place)"
);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$res = $stmt->get_result();

$details = [];
while ($row = $res->fetch_assoc()) {
    $details[(int) $row['id']] = $row;
}
$stmt->close();

/** Title-case, de-duplicate and cap a list of normalised names for display. */
function knn_pretty_list(array $names, int $max = 3): array
{
    $out = [];
    foreach ($names as $n) {
        $n = trim((string) $n);
        if ($n === '') {
            continue;
        }
        $out[ucwords($n)] = true;
        if (count($out) >= $max) {
            break;
        }
    }
    return array_keys($out);
}

/** Join labels into readable text: "A", "A and B", "A, B and C". */
function knn_join_labels(array $labels): string
{
    $labels = array_values($labels);
    $n = count($labels);
    if ($n === 0) return '';
    if ($n === 1) return $labels[0];
    if ($n === 2) return $labels[0] . ' and ' . $labels[1];
    return implode(', ', array_slice($labels, 0, -1)) . ' and ' . end($labels);
}

/** Trim an overview to a short snippet ending on a word boundary. */
function knn_snippet(?string $text, int $limit = 160): string
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

/**
 * Build the "why" for a recommendation: a plain-language reason plus the
 * concrete genres and plot themes shared with the user's taste. Preferring
 * the nearest-neighbour reason keeps it honest -- that film genuinely drove
 * the score, and the shared keywords are the plot elements that connect them.
 *
 * @return array{text:string, genres:string[], themes:string[]}
 */
function knn_reason_bundle(
    array $rec,
    array $candGenres,
    array $candKeywords,
    array $watchedTitle,
    array $watchedGenres,
    array $watchedKeywords,
    string $activeMoodKey,
    array $moodGenres,
    array $highRatedGenres,
    array $watchlistGenres,
    array $prefGenres
): array {
    $sharedGenres = [];
    $sharedThemes = [];
    $text = 'Closely matches your viewing profile';

    $nid = $rec['neighbour_id'] ?? null;

    if ($nid && isset($watchedTitle[$nid])) {
        // The specific watched film that drove this recommendation.
        $sharedGenres = array_values(array_intersect($candGenres, $watchedGenres[$nid] ?? []));
        $sharedThemes = array_values(array_intersect($candKeywords, $watchedKeywords[$nid] ?? []));

        $title = $watchedTitle[$nid];
        $gl = knn_join_labels(knn_pretty_list($sharedGenres, 2));
        $tl = knn_join_labels(knn_pretty_list($sharedThemes, 2));

        if ($gl !== '' && $tl !== '') {
            $text = "Because you watched {$title} — both are {$gl} films exploring {$tl}";
        } elseif ($gl !== '') {
            $text = "Because you watched {$title} — both are {$gl} films";
        } elseif ($tl !== '') {
            $text = "Because you watched {$title} — similar themes: {$tl}";
        } else {
            $text = "Because you watched {$title}";
        }
    } elseif ($activeMoodKey !== '') {
        $matched = array_values(array_intersect($candGenres, $moodGenres));
        if ($matched) {
            $sharedGenres = $matched;
            $gl = knn_join_labels(knn_pretty_list($matched, 2));
            $text = "Fits your '" . ucfirst($activeMoodKey) . "' mood" . ($gl !== '' ? " — {$gl}" : '');
        }
    }

    // Genre-based fallbacks when there was no neighbour / mood match.
    if ($sharedGenres === [] && $sharedThemes === [] && strpos($text, 'Because you watched') !== 0) {
        $hit = null;
        foreach ($candGenres as $g) {
            if (isset($highRatedGenres[$g])) { $hit = $g; break; }
        }
        if ($hit !== null) {
            $sharedGenres = [$hit];
            $text = 'You rated similar ' . ucwords($hit) . ' films highly';
        } else {
            foreach ($candGenres as $g) {
                if (isset($watchlistGenres[$g])) { $hit = $g; break; }
            }
            if ($hit !== null) {
                $sharedGenres = [$hit];
                $text = 'Similar to ' . ucwords($hit) . ' films in your watched list';
            } else {
                foreach ($candGenres as $g) {
                    if (in_array($g, $prefGenres, true)) { $hit = $g; break; }
                }
                if ($hit !== null) {
                    $sharedGenres = [$hit];
                    $text = 'Matches your preferred ' . ucwords($hit) . ' genre';
                }
            }
        }
    }

    return [
        'text'   => $text,
        'genres' => knn_pretty_list($sharedGenres, 3),
        'themes' => knn_pretty_list($sharedThemes, 3),
    ];
}

// Normalise scores to a 0-100 match percentage for display.
// Based on the best score overall, so an "Also for you" card honestly
// shows a lower percentage than a top pick.
$maxScore = max(array_column($ranked, 'score')) ?: 1.0;
$rank = 0;

$out = [];
foreach ($ranked as $rec) {
    $id = $rec['id'];
    if (!isset($details[$id])) {
        continue;
    }
    $d           = $details[$id];
    $movieGenres = knn_split_names($d['genres']);
    $candKeywords = knn_split_names($d['keywords'] ?? '');

    $bundle = knn_reason_bundle(
        $rec, $movieGenres, $candKeywords,
        $watchedTitle, $watchedGenres, $watchedKeywords,
        $activeMoodKey, $moodGenres,
        $highRatedGenres, $watchlistGenres, $prefGenres
    );

    $explanation = $bundle['text'];
    // For a guaranteed Nepali pick with no taste signal, give it context
    // rather than the generic profile line.
    if ($rec['section'] === 'nepali' && $explanation === 'Closely matches your viewing profile') {
        $explanation = 'Handpicked Nepali cinema';
    }

    $sectionLabel = 'Also for you';
    if ($rec['section'] === 'top')    $sectionLabel = 'Top pick';
    if ($rec['section'] === 'nepali') $sectionLabel = 'Nepali cinema';

    $out[] = [
        'rank'             => ++$rank,
        'section'          => $rec['section'],
        'section_label'    => $sectionLabel,
        'movie_id'         => $id,
        'title'            => $d['original_title'],
        'poster'           => !empty($d['poster_path']) ? $d['poster_path'] : 'default.jpg',
        'genres'           => implode(', ', array_map('ucwords', $movieGenres)),
        'predicted_rating' => $rec['predicted_rating'],
        // A guaranteed pick may have no taste score yet; don't show "0% Match".
        'match_percent'    => $rec['score'] > 0 ? (int) round(100 * $rec['score'] / $maxScore) : null,
        'score'            => $rec['score'],
        'explanation'      => $explanation,
        'shared_genres'    => $bundle['genres'],
        'shared_themes'    => $bundle['themes'],
        'overview'         => knn_snippet($d['overview'] ?? '', 160),
        'mood'             => $activeMoodKey !== '' ? ucfirst($activeMoodKey) : null,
    ];
}

$conn->close();
knn_json($out);