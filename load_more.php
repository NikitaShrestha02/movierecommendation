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

    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = 12;
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
        
        $sql = "SELECT * FROM movies WHERE (" . implode(" OR ", $conditions) . ") LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        
        $paramIndex = 1;
        foreach ($params as $p) {
            $stmt->bindValue($paramIndex++, $p, PDO::PARAM_STR);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM movies LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    $movies = $stmt->fetchAll();

    foreach ($movies as $movie) {
        // Use stored poster_path or fallback
        $posterPath = !empty($movie['poster_path']) ? $movie['poster_path'] : 'default.jpg';

        echo '<div class="movie-poster">
                <img src="' . htmlspecialchars($posterPath) . '" alt="' . htmlspecialchars($movie['original_title']) . '">
                <div class="overlay">
                    <button class="' . ($movie['status'] == 'Released' ? 'book-now' : 'coming-soon') . '">
                        ' . ($movie['status'] == 'Released' ? 'Book Now' : 'Coming Soon') . '
                    </button>
                </div>
                <div class="movie-title">' . htmlspecialchars($movie['original_title']) . '</div>
              </div>';
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
