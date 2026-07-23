<?php
session_start();

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

    // Handle "Already Watched" submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_watched'])) {
        $userEmail = $_SESSION['uemail'] ?? null;
        $movieIdPost = (int)($_POST['movie_id'] ?? 0);

        if ($userEmail && $movieIdPost > 0) {
            $userStmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
            $userStmt->execute([$userEmail]);
            $user = $userStmt->fetch();

            if ($user) {
                $userId = $user['id'];
                $insertStmt = $pdo->prepare("INSERT IGNORE INTO watched_movies (user_id, movies_id) VALUES (?, ?)");
                $insertStmt->execute([$userId, $movieIdPost]);
                echo "<script>alert('Marked as watched!');</script>";
            } else {
                echo "<script>alert('User not found.');</script>";
            }
        } else {
            echo "<script>alert('User not logged in.');</script>";
        }
    }

    // Fetch movie by ID
    $movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($movieId <= 0) {
        throw new Exception("Invalid movie ID.");
    }

    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch();

    if (!$movie) {
        throw new Exception("Movie not found.");
    }
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><?php echo htmlspecialchars($movie['original_title']); ?> - Details</title>
<style>
 body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #0D0E30;
    margin: 0;
    padding: 0 20px;
    color: #ddd;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: #1a1c4d;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.6);
    padding: 30px 40px;
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    color: #eee;
}
.poster {
    flex: 1 1 300px;
    max-width: 300px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.8);
}
.poster img {
    width: 100%;
    display: block;
    border-radius: 10px;
}
.details {
    flex: 2 1 500px;
}
h1 {
    margin-top: 0;
    font-size: 2.5rem;
    color: #82aaff;
    margin-bottom: 10px;
}
.description {
    line-height: 1.6;
    font-size: 1.1rem;
    margin-bottom: 25px;
    color: #ccc;
}
.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.info-list li {
    margin-bottom: 12px;
    font-size: 1.1rem;
    color: #ccc;
}
.info-list li strong {
    color: #82aaff;
}
a.back-link, button.back-link {
    display: inline-block;
    margin-top: 25px;
    text-decoration: none;
    color: #82aaff;
    font-weight: 600;
    border: 2px solid #82aaff;
    padding: 8px 18px;
    border-radius: 6px;
    background: none;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
}
a.back-link:hover, button.back-link:hover {
    background-color: #82aaff;
    color: #0D0E30;
}
@media (max-width: 700px) {
    .container {
        flex-direction: column;
        padding: 20px;
    }
    .poster, .details {
        max-width: 100%;
        flex: none;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="poster">
        <?php
        $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : 'images/default-poster.jpg';
        echo '<img src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($movie['original_title']) . ' Poster">';
        ?>
    </div>
    <div class="details">
        <h1><?php echo htmlspecialchars($movie['original_title']); ?></h1>

        <div class="description">
            <?php echo nl2br(htmlspecialchars($movie['overview'] ?? 'No description available.')); ?>
        </div>

        <ul class="info-list">
            <li><strong>Release Date:</strong> <?php echo htmlspecialchars($movie['release_date']); ?></li>
            <li><strong>Genre:</strong> <?php echo htmlspecialchars($movie['genres']); ?></li>
            <li><strong>Runtime:</strong> <?php echo htmlspecialchars($movie['runtime']); ?> minutes</li>
            <li><strong>Original Language:</strong> <?php echo htmlspecialchars($movie['original_language']); ?></li>
            <li><strong>Status:</strong> <?php echo htmlspecialchars($movie['status']); ?></li>
        </ul>

        <form method="POST" style="margin-top: 25px;">
            <input type="hidden" name="movie_id" value="<?php echo $movieId; ?>">
            <button type="submit" name="mark_watched" class="back-link" style="margin-right: 10px;">✓ Already Watched</button>
            <a href="index.php" class="back-link">← Back to Gallery</a>
        </form>
    </div>
</div>

</body>
</html>
