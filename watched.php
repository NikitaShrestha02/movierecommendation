<?php
session_start();

if (!isset($_SESSION['uemail'])) {
    header("Location: login.php");
    exit;
}

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

    // Get user ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$_SESSION['uemail']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }

    $userId = $user['id'];

    // Get watched movies
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM watched_movies wm
        JOIN movies m ON wm.movies_id = m.id
        WHERE wm.user_id = ?
        ORDER BY m.release_date DESC
    ");
    $stmt->execute([$userId]);
    $watchedMovies = $stmt->fetchAll();

} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Watched Movies</title>
    <style>
        /* Reset & base */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0b0b3e 0%, #1f2666 100%);
            color: #f0f0f0;
            min-height: 100vh;
        }

        /* Container */
        .recommendations {
            max-width: 1100px;
            margin: 40px auto;
            padding: 30px 25px;
            background: rgba(30, 40, 90, 0.9);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
        }

        /* Heading */
        .recommendations h2 {
            font-size: 2.4rem;
            text-align: center;
            margin-bottom: 30px;
            letter-spacing: 1px;
            color: #a0c3ff;
            text-shadow: 1px 1px 6px rgba(0,0,0,0.5);
        }

        /* Back link */
        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            padding: 10px 20px;
            background-color: #4a6fff;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(74,111,255,0.6);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .back-link:hover {
            background-color: #3a56cc;
            box-shadow: 0 6px 16px rgba(58,86,204,0.8);
        }

        .container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 22px;
    justify-items: center;
}

/* Individual movie card */
.movie-poster {
    background: #2e3a85;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 15px rgba(0,0,0,0.45);
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    width: 170px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 10px 8px;
}

/* Poster image */
.movie-poster img {
    width: 100%;
    height: 255px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
}
.movie-poster:hover img {
    transform: scale(1.05);
}

        /* Movie title */
        .movie-title {
            margin-top: 12px;
            font-weight: 700;
            font-size: 1rem;
            color: #dbe4ff;
            min-height: 48px;
            line-height: 1.2;
            letter-spacing: 0.03em;
        }

        /* No recommendations message */
        p.no-recommendations {
            margin-top: 60px;
            font-size: 1.25rem;
            color: #99aaffcc;
            text-align: center;
        }

        /* Responsive text */
        @media (max-width: 480px) {
    .recommendations h2 {
        font-size: 1.8rem;
    }
    .movie-title {
        font-size: 0.9rem;
    }
    .movie-poster {
        width: 140px;
        padding: 8px 6px;
    }
    .movie-poster img {
        height: 210px;
    }
}
    </style>
</head>
<body>
<?php
    include("navigation.php");
    ?>
<div class="recommendations">
    <a href="index.php" class="back-link">← Back to Home</a>
    <h2>Watched Movies</h2>

    <?php if (count($watchedMovies) === 0): ?>
        <p class="no-recommendations">You haven't marked any movies as watched yet.</p>
    <?php else: ?>
        <div class="container">
            <?php foreach ($watchedMovies as $movie): ?>
                <div class="movie-poster">
                    <a href="details.php?id=<?php echo $movie['id']; ?>" style="text-decoration:none;">
                        <img src="<?php echo htmlspecialchars($movie['poster_path'] ?: 'images/default-poster.jpg'); ?>" alt="Poster of <?php echo htmlspecialchars($movie['original_title']); ?>">
                        <div class="movie-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>