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

    // Schema guard: Ensure 'rating' column exists in watched_movies table
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM watched_movies LIKE 'rating'");
        if ($colCheck->rowCount() === 0) {
            $pdo->exec("ALTER TABLE watched_movies ADD COLUMN rating INT NULL DEFAULT NULL");
        }
    } catch (Exception $e) {
        // Table or column already ready
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

    $userEmail = $_SESSION['uemail'] ?? null;
    $userId = null;
    $isWatched = false;
    $currentRating = 0;
    $statusMessage = '';

    // Check user watch & rating status if logged in
    if ($userEmail) {
        $userStmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $userRow = $userStmt->fetch();

        if ($userRow) {
            $userId = (int)$userRow['id'];

            // Handle rating & watch submissions
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_rating']) || isset($_POST['mark_watched']))) {
                $submittedRating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
                if ($submittedRating < 1 || $submittedRating > 5) {
                    $submittedRating = null;
                }

                // Check if already in watched_movies
                $checkStmt = $pdo->prepare("SELECT id, rating FROM watched_movies WHERE user_id = ? AND movies_id = ?");
                $checkStmt->execute([$userId, $movieId]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    if ($submittedRating !== null) {
                        $upStmt = $pdo->prepare("UPDATE watched_movies SET rating = ? WHERE user_id = ? AND movies_id = ?");
                        $upStmt->execute([$submittedRating, $userId, $movieId]);
                        $statusMessage = "Rating updated to {$submittedRating} / 5 stars!";
                    } else {
                        $statusMessage = "Movie is already in your watched list!";
                    }
                } else {
                    $inStmt = $pdo->prepare("INSERT INTO watched_movies (user_id, movies_id, rating) VALUES (?, ?, ?)");
                    $inStmt->execute([$userId, $movieId, $submittedRating]);
                    $statusMessage = $submittedRating !== null 
                        ? "Saved to watched list with a {$submittedRating}-star rating!"
                        : "Marked as watched!";
                }
            }

            // Refresh watch & rating status
            $checkStmt = $pdo->prepare("SELECT rating FROM watched_movies WHERE user_id = ? AND movies_id = ?");
            $checkStmt->execute([$userId, $movieId]);
            $watchedRow = $checkStmt->fetch();
            if ($watchedRow !== false) {
                $isWatched = true;
                $currentRating = !empty($watchedRow['rating']) ? (int)$watchedRow['rating'] : 0;
            }
        }
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($movie['original_title']); ?> - Details</title>
<style>
* {
    box-sizing: border-box;
}
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background-color: #0f141c;
    margin: 0;
    padding: 0;
    color: #cbd5e1;
}

.details-wrapper {
    padding: 32px 16px;
}

.details-card {
    max-width: 980px;
    margin: 0 auto;
    background: #181f2c;
    border: 1px solid #242e40;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    padding: 32px;
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
}

.poster-col {
    flex: 1 1 280px;
    max-width: 320px;
}

.poster-col img {
    width: 100%;
    display: block;
    border-radius: 6px;
    border: 1px solid #263245;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.35);
}

.info-col {
    flex: 2 1 480px;
}

h1.movie-title-lg {
    margin-top: 0;
    font-size: 26px;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 14px;
    letter-spacing: -0.01em;
}

.description {
    line-height: 1.6;
    font-size: 15px;
    margin-bottom: 24px;
    color: #94a3b8;
}

.info-list {
    list-style: none;
    padding: 16px 0;
    margin: 0 0 24px 0;
    border-top: 1px solid #242e40;
    border-bottom: 1px solid #242e40;
}

.info-list li {
    margin-bottom: 8px;
    font-size: 14px;
    color: #94a3b8;
    display: flex;
}

.info-list li strong {
    color: #f1f5f9;
    font-weight: 600;
    width: 140px;
    flex-shrink: 0;
}

/* Rating box styling */
.rating-section {
    background: #131924;
    border: 1px solid #263245;
    border-radius: 6px;
    padding: 20px;
}

.rating-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.rating-title {
    font-size: 15px;
    font-weight: 600;
    color: #f1f5f9;
}

.watched-pill {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid #10b981;
    color: #34d399;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
}

.star-rating-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.star-rating {
    display: inline-flex;
    gap: 4px;
}

.star-rating .star {
    font-size: 26px;
    cursor: pointer;
    color: #475569;
    transition: color 0.15s ease, transform 0.15s ease;
    user-select: none;
    line-height: 1;
}

.star-rating .star:hover {
    transform: scale(1.15);
}

.star-rating .star.hovered,
.star-rating .star.active {
    color: #f59e0b;
}

.rating-label {
    font-size: 14px;
    font-weight: 500;
    color: #94a3b8;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-primary-rate {
    background: #2563eb;
    color: #ffffff;
    border: none;
    font-weight: 600;
    font-size: 13.5px;
    padding: 8px 18px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.btn-primary-rate:hover {
    background: #1d4ed8;
}

.btn-secondary-watch {
    background: #1e2637;
    color: #cbd5e1;
    border: 1px solid #2d384c;
    font-weight: 500;
    font-size: 13.5px;
    padding: 7px 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.btn-secondary-watch:hover {
    background: #283449;
    color: #ffffff;
}

.back-link {
    display: inline-block;
    text-decoration: none;
    color: #94a3b8;
    font-weight: 500;
    font-size: 13.5px;
    border: 1px solid #2d384c;
    padding: 7px 16px;
    border-radius: 5px;
    background: transparent;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.back-link:hover {
    background-color: #1e2637;
    color: #f1f5f9;
}

.status-toast {
    margin-top: 12px;
    padding: 8px 12px;
    background: rgba(16, 185, 129, 0.1);
    border-left: 3px solid #10b981;
    color: #34d399;
    font-size: 13px;
    font-weight: 500;
    border-radius: 3px;
}

.login-prompt-box {
    background: #131924;
    border: 1px solid #263245;
    border-radius: 6px;
    padding: 16px;
    color: #94a3b8;
    font-size: 14px;
}

.login-prompt-box a {
    color: #3b82f6;
    font-weight: 600;
    text-decoration: none;
}

.login-prompt-box a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .details-card {
        flex-direction: column;
        padding: 20px;
    }
    .poster-col, .info-col {
        max-width: 100%;
        flex: none;
    }
}
</style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="details-wrapper">
    <div class="details-card">
        <div class="poster-col">
            <?php
            $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : 'images/default-poster.jpg';
            echo '<img src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($movie['original_title']) . ' Poster">';
            ?>
        </div>
        <div class="info-col">
            <h1 class="movie-title-lg"><?php echo htmlspecialchars($movie['original_title']); ?></h1>

            <div class="description">
                <?php echo nl2br(htmlspecialchars($movie['overview'] ?? 'No description available.')); ?>
            </div>

            <ul class="info-list">
                <li><strong>Release Date:</strong> <?php echo htmlspecialchars($movie['release_date']); ?></li>
                <li><strong>Genre:</strong> <?php echo htmlspecialchars($movie['genres']); ?></li>
                <li><strong>Runtime:</strong> <?php echo htmlspecialchars($movie['runtime']); ?> minutes</li>
                <li><strong>Original Language:</strong> <?php echo htmlspecialchars($movie['original_language']); ?></li>
                <li><strong>Status:</strong> <?php echo htmlspecialchars($movie['status']); ?></li>
                <?php if (!empty($movie['vote_average']) || !empty($movie['rating'])): ?>
                    <li><strong>TMDb Rating:</strong> ★ <?php echo htmlspecialchars($movie['vote_average'] ?? $movie['rating']); ?> / 10</li>
                <?php endif; ?>
            </ul>

            <?php if ($userEmail): ?>
                <div class="rating-section">
                    <div class="rating-heading">
                        <span class="rating-title">Your Rating & Watch Status</span>
                        <?php if ($isWatched): ?>
                            <span class="watched-pill">✓ Watched</span>
                        <?php endif; ?>
                    </div>

                    <form method="POST" id="rating-form">
                        <input type="hidden" name="movie_id" value="<?php echo $movieId; ?>">
                        <input type="hidden" name="rating" id="rating-input" value="<?php echo $currentRating; ?>">

                        <div class="star-rating-row">
                            <div class="star-rating" id="star-picker">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <span class="star <?php echo ($s <= $currentRating) ? 'active' : ''; ?>" data-val="<?php echo $s; ?>" title="Rate <?php echo $s; ?> star<?php echo $s > 1 ? 's' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-label" id="rating-text">
                                <?php echo ($currentRating > 0) ? ($currentRating . ' / 5 Stars') : 'Click stars to rate'; ?>
                            </span>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="save_rating" class="btn-primary-rate">
                                ★ <?php echo ($currentRating > 0) ? 'Update Rating' : ($isWatched ? 'Save Rating' : 'Rate & Mark Watched'); ?>
                            </button>

                            <?php if (!$isWatched): ?>
                                <button type="submit" name="mark_watched" class="btn-secondary-watch">
                                    ✓ Mark Watched (Unrated)
                                </button>
                            <?php endif; ?>

                            <a href="index.php" class="back-link">← Back to Home</a>
                        </div>

                        <?php if (!empty($statusMessage)): ?>
                            <div class="status-toast"><?php echo htmlspecialchars($statusMessage); ?></div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php else: ?>
                <div class="login-prompt-box">
                    <p style="margin:0 0 12px 0;">
                        <a href="nlogin.php">Log in</a> to rate this movie, add it to your watched list, and receive personalized KNN recommendations.
                    </p>
                    <a href="index.php" class="back-link">← Back to Home</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include("footer.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const stars = document.querySelectorAll("#star-picker .star");
    const input = document.getElementById("rating-input");
    const label = document.getElementById("rating-text");
    if (!stars.length || !input || !label) return;

    function applyActiveStars(val) {
        stars.forEach(s => {
            const v = parseInt(s.getAttribute("data-val"), 10);
            if (v <= val) {
                s.classList.add("active");
            } else {
                s.classList.remove("active");
            }
        });
    }

    stars.forEach(star => {
        star.addEventListener("mouseenter", function() {
            const hoverVal = parseInt(this.getAttribute("data-val"), 10);
            stars.forEach(s => {
                const v = parseInt(s.getAttribute("data-val"), 10);
                if (v <= hoverVal) {
                    s.classList.add("hovered");
                } else {
                    s.classList.remove("hovered");
                }
            });
            label.textContent = hoverVal + " / 5 Stars";
        });

        star.addEventListener("mouseleave", function() {
            stars.forEach(s => s.classList.remove("hovered"));
            const current = parseInt(input.value, 10) || 0;
            label.textContent = current > 0 ? (current + " / 5 Stars") : "Click stars to rate";
        });

        star.addEventListener("click", function() {
            const chosen = parseInt(this.getAttribute("data-val"), 10);
            input.value = chosen;
            applyActiveStars(chosen);
            label.textContent = chosen + " / 5 Stars (Click Save)";
        });
    });
});
</script>

</body>
</html>
