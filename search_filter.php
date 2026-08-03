<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = "localhost";
$user = "root"; // your DB username
$pass = "";     // your DB password
$db   = "movie_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = $_GET['search'] ?? '';

if (strlen($search) >= 2) {
    $_SESSION['last_search_keyword'] = $search;
} else {
    exit;
}

if (!function_exists('build_vocab')) {
    function build_vocab($text1, $text2) {
        $tokens1 = array_map('trim', preg_split("/[,\s]+/", strtolower($text1), -1, PREG_SPLIT_NO_EMPTY));
        $tokens2 = array_map('trim', preg_split("/[,\s]+/", strtolower($text2), -1, PREG_SPLIT_NO_EMPTY));
        return array_values(array_unique(array_merge($tokens1, $tokens2)));
    }
}

if (!function_exists('vectorize')) {
    function vectorize($text, $vocab) {
        $tokens = array_map('trim', preg_split("/[,\s]+/", strtolower($text), -1, PREG_SPLIT_NO_EMPTY));
        $vector = [];
        foreach ($vocab as $word) {
            $vector[] = in_array($word, $tokens) ? 1 : 0;
        }
        return $vector;
    }
}

if (!function_exists('cosine_similarity')) {
    function cosine_similarity($vec1, $vec2) {
        $dot = 0; $norm1 = 0; $norm2 = 0;
        for ($i = 0; $i < count($vec1); $i++) {
            $dot += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }
        if ($norm1 == 0 || $norm2 == 0) return 0;
        return $dot / (sqrt($norm1) * sqrt($norm2));
    }
}

$search_text = str_replace(" ", ",", $search);
$sql = "SELECT id, original_title, genres, keywords FROM movies";
$result = $conn->query($sql);

$movies = [];
while ($movie = $result->fetch_assoc()) {
    // Include title in the text so direct title searches also match
    $movie_text = $movie['genres'] . "," . $movie['keywords'] . "," . $movie['original_title'];
    
    $shared_vocab = build_vocab($search_text, $movie_text);
    $user_vector = vectorize($search_text, $shared_vocab);
    $movie_vector = vectorize($movie_text, $shared_vocab);
    
    $score = cosine_similarity($user_vector, $movie_vector);
    
    $movies[] = [
        'id' => $movie['id'],
        'title' => $movie['original_title'],
        'score' => $score
    ];
}

usort($movies, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

$found = false;
$count = 0;
$limit = 10;

foreach ($movies as $movie) {
    if ($movie['score'] > 0) {
        $found = true;
        echo "<div class='result-item' onclick=\"window.location.href='details.php?id={$movie['id']}'\">" . htmlspecialchars($movie['title']) . "</div>";
        $count++;
        if ($count >= $limit) break;
    }
}

if (!$found) {
    echo "<div class='result-item'>No movies found for that keyword.</div>";
}

$conn->close();
?>