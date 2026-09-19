<?php
session_start();

if (!isset($_SESSION['uemail'])) {
    header("Location: nlogin.php");
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

    // Schema guard: Ensure 'rating' column exists in watched_movies table
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM watched_movies LIKE 'rating'");
        if ($colCheck->rowCount() === 0) {
            $pdo->exec("ALTER TABLE watched_movies ADD COLUMN rating INT NULL DEFAULT NULL");
        }
    } catch (Exception $e) {
        // Table or column already ready
    }

    // Get user ID
    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$_SESSION['uemail']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }

    $userId = $user['id'];

    // Get watched movies along with user ratings
    $stmt = $pdo->prepare("
        SELECT m.*, wm.rating AS user_rating
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watched Movies</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c;
            color: #cbd5e1;
            min-height: 100vh;
        }

        .page-container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 16px;
        }

        .section-card {
            background: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            padding: 28px 24px;
            margin-bottom: 32px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .back-link {
            display: inline-block;
            padding: 6px 14px;
            background-color: #1e2637;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            border-radius: 5px;
            border: 1px solid #2d384c;
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .back-link:hover {
            background-color: #283449;
            color: #ffffff;
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            justify-items: center;
        }

        .movie-card {
            background: #131924;
            border: 1px solid #263245;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            width: 100%;
            max-width: 185px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 10px;
            text-decoration: none;
        }
        .movie-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.45);
            border-color: #3b82f6;
        }

        .movie-card img {
            width: 100%;
            height: 235px;
            border-radius: 5px;
            object-fit: cover;
            display: block;
        }

        .movie-card-title {
            margin-top: 10px;
            font-weight: 600;
            font-size: 13.5px;
            color: #f1f5f9;
            min-height: 36px;
            line-height: 1.3;
            text-align: center;
        }

        /* User rating badge */
        .user-stars-badge {
            margin-top: 8px;
            padding: 5px 8px;
            background: #1a2230;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #283448;
            width: 100%;
        }
        .user-stars {
            color: #f59e0b;
            font-size: 13px;
            letter-spacing: 1px;
            line-height: 1;
        }
        .user-rating-val {
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
        }
        .unrated-tag {
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
        }

        /* Predicted badge in KNN recommendations */
        .predicted-badge {
            margin-top: 8px;
            padding: 5px 8px;
            background: #1a2230;
            border: 1px solid #283448;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
            color: #f59e0b;
            width: 100%;
        }
        .predicted-badge .badge-label {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .no-data-msg {
            text-align: center;
            color: #94a3b8;
            font-size: 15px;
            padding: 32px 0;
            margin: 0;
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <!-- Watched Movies Section -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Watched Movies</h2>
            <a href="index.php" class="back-link">← Back to Home</a>
        </div>

        <?php if (count($watchedMovies) === 0): ?>
            <p class="no-data-msg">You haven't marked any movies as watched yet. Browse movies and add your ratings!</p>
        <?php else: ?>
            <div class="movie-grid">
                <?php foreach ($watchedMovies as $movie): ?>
                    <a href="details.php?id=<?php echo $movie['id']; ?>" class="movie-card">
                        <img src="<?php echo htmlspecialchars($movie['poster_path'] ?: 'default.jpg'); ?>" alt="Poster of <?php echo htmlspecialchars($movie['original_title']); ?>" onerror="this.onerror=null;this.src='default.jpg';">
                        <div class="movie-card-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                        <div class="user-stars-badge">
                            <?php if (!empty($movie['user_rating'])): ?>
                                <?php $r = (int)$movie['user_rating']; ?>
                                <span class="user-stars"><?php echo str_repeat('★', $r) . str_repeat('☆', 5 - $r); ?></span>
                                <span class="user-rating-val"><?php echo $r; ?>/5</span>
                            <?php else: ?>
                                <span class="unrated-tag">☆ Tap to Rate</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- KNN Recommendations Section -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">Recommended Based on Your Taste</h2>
        </div>
        <div id="knn-recommendations-container">
            <p class="no-data-msg">Calculating recommendations...</p>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const container = document.getElementById("knn-recommendations-container");
        
        fetch("knn_recommendations.php")
            .then(response => response.json())
            .then(data => {
                if (!data || data.length === 0 || data.error) {
                    container.innerHTML = "<p class='no-data-msg'>No recommendations available yet. Watch and rate some movies first!</p>";
                    return;
                }
                
                let html = '<div class="movie-grid">';
                data.forEach(movie => {
                    let predictedBadge = "";
                    if (movie.predicted_rating) {
                        predictedBadge = `
                            <div class="predicted-badge" title="Predicted match score based on your rating profile">
                                <span>★</span> ${movie.predicted_rating} <span class="badge-label">Match</span>
                            </div>
                        `;
                    }

                    html += `
                        <a href="details.php?id=${encodeURIComponent(movie.movie_id)}" class="movie-card">
                            <img src="${movie.poster}" alt="${movie.title}" onerror="this.onerror=null;this.src='default.jpg';">
                            <div class="movie-card-title">${movie.title}</div>
                            ${predictedBadge}
                        </a>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(error => {
                console.error("Error fetching recommendations:", error);
                container.innerHTML = "<p class='no-data-msg' style='color: #f87171;'>Failed to load recommendations.</p>";
            });
    });
</script>

</body>
</html>