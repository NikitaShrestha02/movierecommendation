<?php
/**
 * watched.php  --  "For You" recommendations page
 * ---------------------------------------------------------------
 * This page used to list the user's watched films. That list now
 * lives on the dashboard (userdash.php), and this page is the
 * dedicated home for KNN recommendations.
 *
 * The filename is unchanged so existing links and bookmarks keep
 * working.
 * ---------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['uemail'])) {
    header("Location: nlogin.php");
    exit;
}

$host    = 'localhost';
$db      = 'movie_db';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->prepare("SELECT id, name, preferred_genres FROM user WHERE email = ?");
    $stmt->execute([$_SESSION['uemail']]);
    $userRow = $stmt->fetch();

    if (!$userRow) {
        throw new Exception("User not found.");
    }

    $userId     = (int) $userRow['id'];
    $userName   = $userRow['name'];
    $prefGenres = trim((string) $userRow['preferred_genres']);

    // Just enough context to explain where the recommendations come from
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS watched, COUNT(rating) AS rated
           FROM watched_movies WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    $counts = $stmt->fetch();

    $watchedCount = (int) $counts['watched'];
    $ratedCount   = (int) $counts['rated'];

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
    <title>Recommended For You</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c;
            color: #cbd5e1;
            min-height: 100vh;
        }

        .page-container {
            max-width: 1100px;
            width: 100%;
            margin: 36px auto;
            padding: 0 20px;
        }

        /* ---- Page header ---- */
        .rec-hero {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 26px 28px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
        }

        .rec-hero h1 {
            color: #f8fafc;
            font-size: 23px;
            font-weight: 700;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }

        .rec-hero p {
            margin: 0;
            font-size: 13.5px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .rec-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        .btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 5px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            font-family: inherit;
        }
        .btn-primary   { background-color: #2563eb; color: #fff; border-color: #2563eb; }
        .btn-primary:hover { background-color: #1d4ed8; border-color: #1d4ed8; }
        .btn-secondary { background-color: #1e2637; color: #cbd5e1; border-color: #2d384c; }
        .btn-secondary:hover { background-color: #283449; color: #fff; }

        /* ---- Sections ---- */
        .rec-section-heading {
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            margin: 28px 0 14px;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        .rec-section-heading:first-child { margin-top: 0; }

        .rec-section-note {
            font-size: 11.5px;
            font-weight: 400;
            color: #64748b;
            letter-spacing: 0.02em;
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
            gap: 18px;
        }

        .movie-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 10px;
            text-decoration: none;
            display: block;
            transition: transform 0.18s ease, border-color 0.18s ease;
        }
        .movie-card:hover { transform: translateY(-4px); border-color: #3b82f6; }

        .movie-card img {
            width: 100%;
            height: 232px;
            object-fit: cover;
            border-radius: 6px;
            display: block;
            background-color: #131924;
        }

        .movie-card-title {
            color: #f1f5f9;
            font-size: 13.5px;
            font-weight: 600;
            margin-top: 9px;
            line-height: 1.3;
        }

        .match-badge {
            font-size: 11px;
            color: #f59e0b;
            font-weight: 600;
            background-color: #1a2230;
            border: 1px solid #283448;
            padding: 3px 7px;
            border-radius: 4px;
            margin-top: 7px;
            display: inline-block;
        }

        .rec-reason {
            font-size: 11.5px;
            color: #94a3b8;
            margin-top: 6px;
            line-height: 1.35;
        }

        /* ---- Empty / loading states ---- */
        .rec-state {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 40px 28px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
        }
        .rec-state strong { color: #e2e8f0; display: block; margin-bottom: 8px; font-size: 15.5px; }
        .rec-state .btn { margin-top: 16px; }

        @media (max-width: 600px) {
            .movie-grid { grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); }
            .movie-card img { height: 186px; }
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">

    <div class="rec-hero">
        <div>
            <h1>Recommended for you, <?php echo htmlspecialchars($userName); ?></h1>
            <p>
                <?php if ($watchedCount > 0): ?>
                    Based on <strong><?php echo $watchedCount; ?></strong>
                    film<?php echo $watchedCount === 1 ? '' : 's'; ?> you've watched<?php
                        echo $ratedCount > 0 ? " ({$ratedCount} rated)" : '';
                    ?><?php echo $prefGenres !== '' ? ' and your preferred genres' : ''; ?>.
                <?php else: ?>
                    You haven't marked any films as watched yet &mdash; these are based on your
                    preferred genres for now.
                <?php endif; ?>
            </p>
        </div>
        <div class="rec-hero-actions">
            <a href="userdash.php" class="btn btn-secondary">My watched films</a>
            <a href="index.php" class="btn btn-primary">Browse movies</a>
        </div>
    </div>

    <div id="knn-recommendations-container">
        <div class="rec-state">Finding films for you&hellip;</div>
    </div>

</div>

<?php include("footer.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById("knn-recommendations-container");
    const watchedCount = <?php echo (int) $watchedCount; ?>;

    const esc = str => String(str == null ? "" : str).replace(
        /[&<>"']/g,
        c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])
    );

    const card = movie => `
        <a href="details.php?id=${encodeURIComponent(movie.movie_id)}" class="movie-card">
            <img src="${esc(movie.poster)}" alt="${esc(movie.title)}"
                 onerror="this.onerror=null;this.src='default.jpg';">
            <div class="movie-card-title">${esc(movie.title)}</div>
            ${movie.match_percent != null
                ? `<div class="match-badge">★ ${movie.match_percent}% Match</div>` : ''}
            ${movie.explanation
                ? `<div class="rec-reason">${esc(movie.explanation)}</div>` : ''}
        </a>`;

    fetch("knn_recommendations.php")
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0 || data.error) {
                container.innerHTML = watchedCount === 0
                    ? `<div class="rec-state">
                         <strong>Nothing to recommend yet</strong>
                         Mark a few films as watched and rate them &mdash; the more you rate,
                         the sharper these get.
                         <div><a href="index.php" class="btn btn-primary">Browse movies</a></div>
                       </div>`
                    : `<div class="rec-state">
                         <strong>No recommendations right now</strong>
                         Try watching a few more films, or adjust your preferred genres.
                         <div><a href="userdash.php" class="btn btn-secondary">Go to dashboard</a></div>
                       </div>`;
                return;
            }

            const top  = data.filter(m => m.section === "top");
            const more = data.filter(m => m.section === "more");

            let html = "";

            if (top.length) {
                html += `<h2 class="rec-section-heading">Top picks for you
                           <span class="rec-section-note">ranked by your viewing history</span>
                         </h2>
                         <div class="movie-grid">${top.map(card).join("")}</div>`;
            }
            if (more.length) {
                html += `<h2 class="rec-section-heading">Also for you
                           <span class="rec-section-note">refreshes daily</span>
                         </h2>
                         <div class="movie-grid">${more.map(card).join("")}</div>`;
            }
            if (!html) {
                html = `<div class="movie-grid">${data.map(card).join("")}</div>`;
            }

            container.innerHTML = html;
        })
        .catch(error => {
            console.error("Error fetching recommendations:", error);
            container.innerHTML = `<div class="rec-state" style="color:#f87171;">
                Failed to load recommendations. Please refresh the page.
            </div>`;
        });
});
</script>

</body>
</html>