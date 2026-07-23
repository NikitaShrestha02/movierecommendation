<?php
$host = 'localhost';
$db = 'movie_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $limit = 12;

   
    $genreStmt = $pdo->query("SELECT DISTINCT genres FROM movies WHERE genres IS NOT NULL AND genres <> ''");
    $genres = $genreStmt->fetchAll();

    $selectedGenre = isset($_GET['genre']) ? $_GET['genre'] : '';

    if ($selectedGenre && $selectedGenre !== 'all') {
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE genres = ? LIMIT ?");
        $stmt->bindValue(1, $selectedGenre, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM movies LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    $movies = $stmt->fetchAll();

    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Movie Gallery</title>
        <link href="css/poster.css" rel="stylesheet">
        <style>
    #loadMore {
        display: block;
        margin: 20px auto;
        padding: 10px 20px;
        font-size: 16px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .filter-container {
        text-align: center;
        margin: 30px 0;
    }

    .filter-label {
        font-size: 18px;
        font-weight: bold;
        margin-right: 10px;
        color: white;
    }

    .filter-select {
        padding: 10px 15px;
        font-size: 16px;
        border-radius: 8px;
        border: 1px solid #ccc;
        background-color: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-select:hover,
    .filter-select:focus {
        border-color:rgb(48, 99, 142);
        background-color: #fff;
        outline: none;
    }

    /* Show Details button */
    .show-details {
        background-color:rgb(78, 107, 221);
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .show-details:hover {
        background-color: #218838;
        box-shadow: 0 4px 8px rgba(33, 136, 56, 0.4);
    }

    .show-details:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5);
    }
        .movie-title{
        font-size: 20px;
}
</style>

    </head>
    <body>
    <?php 
    include("navigation.php");
    ?>
    <h1 class="headline">Movie Gallery</h1>';

    // 🔽 Genre Filter
    echo '<div class="filter-container">
        <form method="GET">
            <label for="genre" class="filter-label">🎬 Filter by Genre:</label>
            <select name="genre" class="filter-select" onchange="this.form.submit()">
                <option value="all">All</option>';
                foreach ($genres as $genre) {
                    $g = htmlspecialchars($genre['genres']);
                    $selected = ($selectedGenre === $genre['genres']) ? 'selected' : '';
                    echo "<option value=\"$g\" $selected>$g</option>";
                }
    echo '  </select>
        </form>
    </div>';

    // 🔽 Movie Grid
echo '<div class="container" id="movie-container">';
foreach ($movies as $movie) {
    $posterPath = !empty($movie['poster_path']) ? $movie['poster_path'] : 'images/default-poster.jpg';

    echo '<div class="movie-poster">
            <img src="' . htmlspecialchars($posterPath) . '" alt="' . htmlspecialchars($movie['original_title']) . '">
            <div class="overlay">';
    
    if ($movie['status'] == 'Released') {
        echo '<a href="details.php?id=' . urlencode($movie['id']) . '" class="show-details">Show Details</a>';
    } else {
        echo '<button class="coming-soon">Coming Soon</button>';
    }

    echo '  </div>
            <div class="movie-title">' . htmlspecialchars($movie['original_title']) . '</div>
          </div>';
}

echo '</div>';

    echo '<button id="loadMore">Show More</button>';

    echo '<script>
        let offset = ' . count($movies) . ';
        const button = document.getElementById("loadMore");
        const genre = "' . htmlspecialchars($selectedGenre) . '";

        button.addEventListener("click", function () {
            fetch("load_more.php?offset=" + offset + "&genre=" + encodeURIComponent(genre))
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "") {
                        button.style.display = "none";
                    } else {
                        document.getElementById("movie-container").insertAdjacentHTML("beforeend", data);
                        offset += ' . $limit . ';
                    }
                });
        });
    </script>
    </body>
    </html>';

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
