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
$watched      = [];
$watchedTitle = [];

$stmt = $conn->prepare(
    "SELECT m.id, m.original_title, m.genres, wm.rating
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

    foreach (knn_split_names($row['genres']) as $g) {
        $watchlistGenres[$g] = true;
        if ((float) $row['rating'] >= 4.0) {
            $highRatedGenres[$g] = true;
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

if (!$ranked) {
    knn_json([]);
}

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
// Hydrate with display data
// ---------------------------------------------------------------
$ids   = array_column($ranked, 'id');
$place = implode(',', array_fill(0, count($ids), '?'));

$stmt = $conn->prepare(
    "SELECT id, original_title, poster_path, genres FROM movies WHERE id IN ($place)"
);
$stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
$stmt->execute();
$res = $stmt->get_result();

$details = [];
while ($row = $res->fetch_assoc()) {
    $details[(int) $row['id']] = $row;
}
$stmt->close();

/**
 * Explain the recommendation, preferring the most specific reason.
 * The nearest-neighbour explanation is the honest one: that film
 * genuinely drove the score.
 */
function knn_explain(
    array $rec,
    array $watchedTitle,
    array $movieGenres,
    string $activeMoodKey,
    array $moodGenres,
    array $highRatedGenres,
    array $watchlistGenres,
    array $prefGenres
): string {
    if (!empty($rec['neighbour_id']) && isset($watchedTitle[$rec['neighbour_id']])) {
        return 'Because you watched ' . $watchedTitle[$rec['neighbour_id']];
    }

    if ($activeMoodKey !== '') {
        foreach ($movieGenres as $g) {
            if (in_array($g, $moodGenres, true)) {
                return "Fits your '" . ucfirst($activeMoodKey) . "' mood";
            }
        }
    }

    foreach ($movieGenres as $g) {
        if (isset($highRatedGenres[$g])) {
            return 'You rated similar ' . ucwords($g) . ' films highly';
        }
    }
    foreach ($movieGenres as $g) {
        if (isset($watchlistGenres[$g])) {
            return 'Similar to films in your watched list';
        }
    }
    foreach ($movieGenres as $g) {
        if (in_array($g, $prefGenres, true)) {
            return 'Matches your preferred genres (' . ucwords($g) . ')';
        }
    }
    return 'Closely matches your viewing profile';
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

    $out[] = [
        'rank'             => ++$rank,
        'section'          => $rec['section'],
        'section_label'    => $rec['section'] === 'top' ? 'Top pick' : 'Also for you',
        'movie_id'         => $id,
        'title'            => $d['original_title'],
        'poster'           => !empty($d['poster_path']) ? $d['poster_path'] : 'default.jpg',
        'genres'           => implode(', ', array_map('ucwords', $movieGenres)),
        'predicted_rating' => $rec['predicted_rating'],
        'match_percent'    => (int) round(100 * $rec['score'] / $maxScore),
        'score'            => $rec['score'],
        'explanation'      => knn_explain(
            $rec, $watchedTitle, $movieGenres, $activeMoodKey,
            $moodGenres, $highRatedGenres, $watchlistGenres, $prefGenres
        ),
        'mood'             => $activeMoodKey !== '' ? ucfirst($activeMoodKey) : null,
    ];
}

$conn->close();
knn_json($out);