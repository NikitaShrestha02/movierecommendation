<?php
error_reporting(0); // Suppress warnings that could break JSON output
// Session check matching userdash.php and index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}

if (!isset($_SESSION['uemail'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

include('connection.php');
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Schema guard: Ensure 'rating' column exists in watched_movies table
$colCheck = $conn->query("SHOW COLUMNS FROM watched_movies LIKE 'rating'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE watched_movies ADD COLUMN rating INT NULL DEFAULT NULL");
}

$userEmail = $_SESSION['uemail'];

// Get user ID
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode([]);
    exit;
}
$userRow = $result->fetch_assoc();
$userId = $userRow['id'];
$stmt->close();

// Fetch watched movies and their details along with user ratings
$watched_sql = "
    SELECT m.*, wm.rating AS user_rating
    FROM watched_movies wm
    JOIN movies m ON wm.movies_id = m.id
    WHERE wm.user_id = ?
";
$stmt = $conn->prepare($watched_sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$watched_result = $stmt->get_result();

$watched_movies = [];
$watched_movie_ids = [];
while ($row = $watched_result->fetch_assoc()) {
    $watched_movies[] = $row;
    $watched_movie_ids[] = $row['id'];
}
$stmt->close();

if (empty($watched_movies)) {
    echo json_encode([]); // No watched movies, can't compute centroid
    exit;
}

/**
 * Helper: Safely extracts TMDb rating from the movie array and normalizes it to a 0-1 scale.
 * Assumes a 0-10 rating scale.
 */
function get_rating_norm($movie) {
    $r = $movie['rating'] ?? $movie['vote_average'] ?? $movie['average_rating'] ?? 0;
    return floatval($r) / 10.0; 
}

// ---------------------------------------------------------
// 1. Build a shared genre vocabulary from ALL distinct genres
// ---------------------------------------------------------
$vocab = [];
$all_genres_sql = "SELECT genres FROM movies WHERE genres IS NOT NULL AND genres != ''";
$all_genres_res = $conn->query($all_genres_sql);
while ($row = $all_genres_res->fetch_assoc()) {
    $tokens = array_map('trim', preg_split("/[,\s]+/", strtolower((string)$row['genres']), -1, PREG_SPLIT_NO_EMPTY));
    foreach ($tokens as $token) {
        if (!in_array($token, $vocab)) {
            $vocab[] = $token;
        }
    }
}

// Ensure vocabulary is not completely empty
if (empty($vocab)) {
    echo json_encode([]);
    exit;
}

/**
 * Helper: Build Feature Vector.
 * Vector X = [g_1, g_2, ..., g_N, rating_norm] where g_i is 1 if present, else 0
 */
function build_feature_vector($genres_str, $rating_norm, $vocab) {
    $tokens = array_map('trim', preg_split("/[,\s]+/", strtolower((string)$genres_str), -1, PREG_SPLIT_NO_EMPTY));
    $vector = [];
    foreach ($vocab as $word) {
        $vector[] = in_array($word, $tokens) ? 1.0 : 0.0;
    }
    // Append normalized TMDb rating dimension at the end
    $vector[] = $rating_norm;
    return $vector;
}

// ---------------------------------------------------------
// 2. Compute WEIGHTED CENTROID vector for watched movies
// ---------------------------------------------------------
// Incorporates the user rating system: movies with higher user ratings (e.g. 5 stars)
// contribute with significantly higher weight to the preference centroid.
$num_dims = count($vocab) + 1; // +1 for the appended rating dimension
$centroid = array_fill(0, $num_dims, 0.0);
$total_user_weight = 0.0;
$watched_profiles = [];

foreach ($watched_movies as $wm) {
    $raw_user_rating = !empty($wm['user_rating']) ? floatval($wm['user_rating']) : 3.0;
    // Normalize user rating to a weight factor (5★ -> 1.0, 4★ -> 0.8, 3★ -> 0.6, 2★ -> 0.35, 1★ -> 0.15)
    $weight = max(0.15, $raw_user_rating / 5.0);
    $total_user_weight += $weight;

    $rating_norm = get_rating_norm($wm);
    $vec = build_feature_vector($wm['genres'], $rating_norm, $vocab);

    $watched_profiles[] = [
        'vector' => $vec,
        'user_rating' => $raw_user_rating
    ];

    for ($i = 0; $i < $num_dims; $i++) {
        $centroid[$i] += $vec[$i] * $weight;
    }
}

// Normalize each dimension sum by the total user weight
if ($total_user_weight > 0) {
    for ($i = 0; $i < $num_dims; $i++) {
        $centroid[$i] = $centroid[$i] / $total_user_weight;
    }
}

// ---------------------------------------------------------
// 3. Configurable Weight Multiplier for TMDb Rating Dimension
// ---------------------------------------------------------
$ratingWeight = 1.0; 

// Retrieve all unwatched movies (exclude movies in watched list)
$unwatched_sql = "SELECT * FROM movies WHERE id NOT IN (" . implode(',', $watched_movie_ids) . ")";
$unwatched_res = $conn->query($unwatched_sql);

$recommendations = [];
while ($movie = $unwatched_res->fetch_assoc()) {
    $rating_norm = get_rating_norm($movie);
    $movie_vec = build_feature_vector($movie['genres'], $rating_norm, $vocab);
    
    // ---------------------------------------------------------
    // 4. Compute Euclidean distance between Centroid and Unwatched Movie
    // ---------------------------------------------------------
    $sum_sq = 0.0;
    for ($i = 0; $i < count($vocab); $i++) {
        $sum_sq += pow($centroid[$i] - $movie_vec[$i], 2);
    }
    
    $rating_idx = $num_dims - 1;
    $sum_sq += $ratingWeight * pow($centroid[$rating_idx] - $movie_vec[$rating_idx], 2);
    $distance = sqrt($sum_sq);

    // ---------------------------------------------------------
    // 5. Predict Rating via KNN Regression against Watched Movies
    // ---------------------------------------------------------
    // Uses distance-weighted average of user's ratings for watched movies
    $weighted_rating_sum = 0.0;
    $inv_dist_sum = 0.0;

    foreach ($watched_profiles as $wp) {
        $w_sum_sq = 0.0;
        for ($i = 0; $i < count($vocab); $i++) {
            $w_sum_sq += pow($wp['vector'][$i] - $movie_vec[$i], 2);
        }
        $w_sum_sq += $ratingWeight * pow($wp['vector'][$rating_idx] - $movie_vec[$rating_idx], 2);
        $w_dist = sqrt($w_sum_sq);

        $inv_dist = 1.0 / ($w_dist + 0.05);
        $weighted_rating_sum += $inv_dist * $wp['user_rating'];
        $inv_dist_sum += $inv_dist;
    }

    $predicted_rating = 3.5;
    if ($inv_dist_sum > 0) {
        $predicted_rating = $weighted_rating_sum / $inv_dist_sum;
    }
    $predicted_rating = round(min(5.0, max(1.0, $predicted_rating)), 1);
    
    $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : "default.jpg";
    $raw_rating = $movie['rating'] ?? $movie['vote_average'] ?? $movie['average_rating'] ?? 0;
    
    $recommendations[] = [
        'movie_id' => $movie['id'],
        'title' => $movie['original_title'],
        'poster' => $poster,
        'rating' => floatval($raw_rating),
        'predicted_rating' => $predicted_rating,
        'distance_score' => round($distance, 4)
    ];
}

// ---------------------------------------------------------
// 6. Select K=10 smallest distance movies
// ---------------------------------------------------------
usort($recommendations, function ($a, $b) {
    return $a['distance_score'] <=> $b['distance_score'];
});

// Take top K=10
$top_k = array_slice($recommendations, 0, 10);

header('Content-Type: application/json');
echo json_encode($top_k);
$conn->close();
?>
