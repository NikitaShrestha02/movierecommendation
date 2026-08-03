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

// Fetch watched movies and their details
// We check multiple rating columns in case schema varies (rating, vote_average, average_rating)
$watched_sql = "
    SELECT m.*
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
 * Helper: Safely extracts rating from the movie array and normalizes it to a 0-1 scale.
 * Assumes a 0-10 rating scale.
 */
function get_rating_norm($movie) {
    $r = $movie['rating'] ?? $movie['vote_average'] ?? $movie['average_rating'] ?? 0;
    return floatval($r) / 10.0; 
}

// ---------------------------------------------------------
// 1. Build a shared genre vocabulary from ALL distinct genres
// ---------------------------------------------------------
// (Math step: Extract all distinct tokens from the genres column to form vocabulary V)
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
 * (Math step: Creates vector X = [g_1, g_2, ..., g_N, rating_norm] where g_i is 1 if present, else 0)
 */
function build_feature_vector($genres_str, $rating_norm, $vocab) {
    $tokens = array_map('trim', preg_split("/[,\s]+/", strtolower((string)$genres_str), -1, PREG_SPLIT_NO_EMPTY));
    $vector = [];
    foreach ($vocab as $word) {
        $vector[] = in_array($word, $tokens) ? 1.0 : 0.0;
    }
    // Append normalized rating dimension at the end
    $vector[] = $rating_norm;
    return $vector;
}

// ---------------------------------------------------------
// 2. Compute CENTROID vector for watched movies
// ---------------------------------------------------------
// (Math step: C_i = Average of all watched movie vectors at dimension i, including the rating dimension)
$num_dims = count($vocab) + 1; // +1 for the appended rating dimension
$centroid = array_fill(0, $num_dims, 0.0);
$num_watched = count($watched_movies);

foreach ($watched_movies as $wm) {
    $rating_norm = get_rating_norm($wm);
    $vec = build_feature_vector($wm['genres'], $rating_norm, $vocab);
    for ($i = 0; $i < $num_dims; $i++) {
        $centroid[$i] += $vec[$i];
    }
}

// Average out each dimension sum
for ($i = 0; $i < $num_dims; $i++) {
    $centroid[$i] = $centroid[$i] / $num_watched;
}

// ---------------------------------------------------------
// 3. Configurable Weight Multiplier for Rating Dimension
// ---------------------------------------------------------
// This hyperparameter controls how much the rating similarity matters compared to genre similarity.
// A higher value forces the algorithm to prioritize rating closeness over genre matching.
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
    // Math step: distance = sqrt( sum_{i=0..N-1} (C_i - U_i)^2 + ratingWeight * (C_N - U_N)^2 )
    $sum_sq = 0.0;
    
    // Iterate over genre dimensions (0 to num_dims - 2)
    for ($i = 0; $i < count($vocab); $i++) {
        $sum_sq += pow($centroid[$i] - $movie_vec[$i], 2);
    }
    
    // Apply weighting specifically to the rating dimension (the last index)
    $rating_idx = $num_dims - 1;
    $sum_sq += $ratingWeight * pow($centroid[$rating_idx] - $movie_vec[$rating_idx], 2);
    
    $distance = sqrt($sum_sq);
    
    $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : "default.jpg";
    $raw_rating = $movie['rating'] ?? $movie['vote_average'] ?? $movie['average_rating'] ?? 0;
    
    $recommendations[] = [
        'movie_id' => $movie['id'],
        'title' => $movie['original_title'],
        'poster' => $poster,
        'rating' => floatval($raw_rating),
        'distance_score' => round($distance, 4)
    ];
}

// ---------------------------------------------------------
// 5. Select K=10 smallest distance movies
// ---------------------------------------------------------
// Sort unwatched movies by distance ASC (closest to centroid first)
usort($recommendations, function ($a, $b) {
    return $a['distance_score'] <=> $b['distance_score'];
});

// Take top K=10
$top_k = array_slice($recommendations, 0, 10);

header('Content-Type: application/json');
echo json_encode($top_k);
$conn->close();
?>
