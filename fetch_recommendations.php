<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$host = "localhost";
$user = "root";
$pass = "";
$db   = "movie_db";

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

$search = $_GET['search'] ?? ($_SESSION['last_search_keyword'] ?? '');

if (strlen($search) >= 2) {
    $_SESSION['last_search_keyword'] = $search;
}

if (strlen($search) < 2) {
    echo '<div class="recommendations"><h1>Recommended Movies</h1><p class="no-recommendations">Search for something to see recommendations.</p></div>';
} else {

    function build_vocab($text1, $text2) {
        $tokens1 = array_map('trim', preg_split("/[,\s]+/", strtolower($text1), -1, PREG_SPLIT_NO_EMPTY));
        $tokens2 = array_map('trim', preg_split("/[,\s]+/", strtolower($text2), -1, PREG_SPLIT_NO_EMPTY));
        return array_values(array_unique(array_merge($tokens1, $tokens2)));
    }

    function vectorize($text, $vocab) {
        $tokens = array_map('trim', preg_split("/[,\s]+/", strtolower($text), -1, PREG_SPLIT_NO_EMPTY));
        $vector = [];
        foreach ($vocab as $word) {
            $vector[] = in_array($word, $tokens) ? 1 : 0;
        }
        return $vector;
    }

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

    $search_text = str_replace(" ", ",", $search);

    $sql = "SELECT id, original_title, genres, keywords, poster_path FROM movies";
    $result = $conn->query($sql);

    $movies = [];
    while ($movie = $result->fetch_assoc()) {
        $movie_text = $movie['genres'] . "," . $movie['keywords'];
        
        $shared_vocab = build_vocab($search_text, $movie_text);
        
        $user_vector = vectorize($search_text, $shared_vocab);
        $movie_vector = vectorize($movie_text, $shared_vocab);
        
        $score = cosine_similarity($user_vector, $movie_vector);

        $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : "default.jpg";

        $movies[] = [
            'id' => $movie['id'],
            'title' => $movie['original_title'],
            'score' => $score,
            'poster_src' => $poster
        ];
    }

    usort($movies, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    echo '<div class="recommendations"><h1>Recommended Movies for "' . htmlspecialchars($search) . '"</h1><div class="movie-container">';
    $found = false;
    $count = 0;
    $limit = 10;

    foreach ($movies as $movie) {
        if ($movie['score'] > 0) {
            $found = true;
            $id = $movie['id'];
            $title = htmlspecialchars($movie['title']);
            $poster = $movie['poster_src'];

            echo '<div class="movie-poster">';
            echo "<img src='{$poster}' alt='{$title}' onerror=\"this.onerror=null;this.src='default.jpg';\">";
            echo '<div class="overlay">';
            echo "<div class='movie-title'>{$title}</div>";
            echo "<a href='details.php?id=" . urlencode($id) . "' class='show-details'>Show Details</a>";
            echo '</div>';
            echo '</div>';

            $count++;
            if ($count >= $limit) break;
        }
    }

    if (!$found) {
        echo "<p class='no-recommendations'>No recommendations found for your search.</p>";
    }

    echo '</div></div>';
}

// Only close connection if called standalone via AJAX (not included in parent script)
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'fetch_recommendations.php') {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            @$conn->close();
        } catch (Throwable $e) {}
    }
}
?>
