<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "movie_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<html>
<head>
    <title>Movie Recommendation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0D0E30; 
            margin: 0;
            padding: 20px;
            color: white;
        }

        .recommendations {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 30px auto;
            background-color: #1A1B4B;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.6s ease-in-out;
        }

        .recommendations h1 {
            text-align: left;
            font-size: 36px;
            margin-bottom: 30px;
            color: #ffffff;
            border-left: 6px solid #28a745;
            padding-left: 12px;
        }

        .movie-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
        }

        .no-recommendations {
            text-align: center;
            color: #bbb;
            font-size: 18px;
            padding: 20px;
        }

        .movie-poster {
            width: 200px;
            height: 300px;
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            background: #111;
        }

        .movie-poster:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 25px rgba(0, 255, 128, 0.3);
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(13, 14, 48, 0.9);
            opacity: 0;
            transition: opacity 0.4s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 10px;
        }

        .movie-poster:hover .overlay {
            opacity: 1;
        }

        .movie-title {
            font-size: 18px;
            color: #fff;
            text-align: center;
            margin-bottom: 15px;
            padding: 0 10px;
            font-weight: bold;
        }

        .show-details {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 15px;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .show-details:hover {
            background: linear-gradient(135deg, #34d058, #218838);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php
include("navigation.php");
include("slideshow.php");

$search = $_SESSION['last_search_keyword'] ?? '';

if (strlen($search) < 2) {
    echo '<div class="recommendations"><h1>Recommended Movies</h1><p class="no-recommendations">Search for something to see recommendations.</p></div>';
} else {

    function build_vocab($search) {
        return array_unique(array_map('trim', explode(" ", strtolower($search))));
    }

    function vectorize($text, $vocab) {
        $tokens = array_map('trim', preg_split("/[,\s]+/", strtolower($text)));
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

    $vocab = build_vocab($search);
    $user_vector = vectorize(str_replace(" ", ",", $search), $vocab);

    $sql = "SELECT id, original_title, genres, keywords, poster_path FROM movies";
    $result = $conn->query($sql);

    $movies = [];
    while ($movie = $result->fetch_assoc()) {
        $movie_text = $movie['genres'] . "," . $movie['keywords'];
        $movie_vector = vectorize($movie_text, $vocab);
        $score = cosine_similarity($user_vector, $movie_vector);

        $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : "posters/default.jpg";

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
            echo "<img src='{$poster}' alt='{$title}' onerror=\"this.onerror=null;this.src='posters/default.jpg';\">";
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

$conn->close();
include("footer.php");
?>

</body>
</html>
