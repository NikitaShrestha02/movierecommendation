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

   
    $genreList = [
    "Action",
    "Adventure",
    "Animation",
    "Comedy",
    "Crime",
    "Documentary",
    "Drama",
    "Family",
    "Fantasy",
    "History",
    "Horror",
    "Music",
    "Mystery",
    "Romance",
    "Science Fiction",
    "TV Movie",
    "Thriller",
    "War",
    "Western"
];

    $selectedGenres = isset($_GET['genre']) ? $_GET['genre'] : [];
    if (!is_array($selectedGenres)) {
        $selectedGenres = ($selectedGenres !== '' && $selectedGenres !== 'all') ? [$selectedGenres] : [];
    }

    if (!empty($selectedGenres)) {
        $conditions = [];
        $params = [];
        foreach ($selectedGenres as $g) {
            $conditions[] = "genres LIKE ?";
            $params[] = "%" . $g . "%";
        }
        $sql = "SELECT * FROM movies WHERE (" . implode(" OR ", $conditions) . ") LIMIT ?";
        $stmt = $pdo->prepare($sql);
        
        $paramIndex = 1;
        foreach ($params as $p) {
            $stmt->bindValue($paramIndex++, $p, PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);
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
        color: white;
        display: block;
        margin-bottom: 15px;
    }

    .genre-bubbles {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    .genre-bubble {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        background-color: #f8f9fa;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        border: 1px solid #ccc;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .genre-bubble:hover {
        background-color: #e2e6ea;
        border-color: rgb(48, 99, 142);
    }
    .genre-bubble.active {
        background-color: rgb(48, 99, 142);
        color: white;
        border-color: rgb(48, 99, 142);
    }
    .clear-genre-btn {
        background-color: #dc3545;
        color: white;
        border-color: #dc3545;
    }
    .clear-genre-btn:hover {
        background-color: #c82333;
        border-color: #bd2130;
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
        <label class="filter-label">🎬 Filter by Genre:</label>
        <div class="genre-bubbles">';
        
    foreach ($genreList as $genre) {
        $isActive = in_array($genre, $selectedGenres);
        $activeClass = $isActive ? 'active' : '';
        
        $newGenres = $selectedGenres;
        if ($isActive) {
            $newGenres = array_diff($newGenres, [$genre]);
        } else {
            $newGenres[] = $genre;
        }
        
        $queryString = '';
        foreach ($newGenres as $ng) {
            $queryString .= '&genre[]=' . urlencode($ng);
        }
        $queryString = !empty($queryString) ? '?' . substr($queryString, 1) : '?genre=all';

        echo '<a href="' . $queryString . '" class="genre-bubble ' . $activeClass . '">' . htmlspecialchars($genre) . '</a>';
    }
    
    echo '<a href="?genre=all" class="genre-bubble clear-genre-btn">Clear all genre</a>';
    echo '  </div>
    </div>';

    // 🔽 Movie Grid
echo '<div class="container" id="movie-container">';
foreach ($movies as $movie) {
    $posterPath = !empty($movie['poster_path']) ? $movie['poster_path'] : 'default.jpg';

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

    $jsGenres = json_encode($selectedGenres);
    echo '<script>
        let offset = ' . count($movies) . ';
        const button = document.getElementById("loadMore");
        const genres = ' . $jsGenres . ';

        button.addEventListener("click", function () {
            let genreQuery = genres.map(g => "genre[]=" + encodeURIComponent(g)).join("&");
            fetch("load_more.php?offset=" + offset + "&" + genreQuery)
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
