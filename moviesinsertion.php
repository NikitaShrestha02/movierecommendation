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
        margin: 28px auto;
        padding: 9px 22px;
        font-size: 14px;
        font-weight: 600;
        background-color: #1e2637;
        color: #cbd5e1;
        border: 1px solid #2d384c;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
    #loadMore:hover {
        background-color: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    .filter-container {
        text-align: center;
        margin: 24px auto;
        max-width: 1200px;
        padding: 0 16px;
    }

    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: #94a3b8;
        display: block;
        margin-bottom: 12px;
        letter-spacing: 0.02em;
    }

    .genre-bubbles {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
    }
    .genre-bubble {
        display: inline-block;
        padding: 6px 13px;
        border-radius: 6px;
        background-color: #1a2230;
        color: #cbd5e1;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        border: 1px solid #2d384c;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .genre-bubble:hover {
        background-color: #243044;
        color: #ffffff;
        border-color: #3b82f6;
    }
    .genre-bubble.active {
        background-color: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
    .clear-genre-btn {
        background-color: #451a1a;
        color: #fca5a5;
        border-color: #7f1d1d;
    }
    .clear-genre-btn:hover {
        background-color: #7f1d1d;
        color: #ffffff;
        border-color: #991b1b;
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
