<?php
session_start();
if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}
if (!isset($_SESSION['uemail']) || $_SESSION['uemail'] !== 'snadmin@gmail.com') {
    header("Location: adform.php");
    exit();
}

include('connection.php');

// Fetch summary metrics
$totalMovies = 0;
$totalUsers = 0;
$totalRatings = 0;

$mRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM movies");
if ($mRes) $totalMovies = (int)mysqli_fetch_assoc($mRes)['cnt'];

$uRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM user");
if ($uRes) $totalUsers = (int)mysqli_fetch_assoc($uRes)['cnt'];

$rRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM watched_movies WHERE rating IS NOT NULL");
if ($rRes) $totalRatings = (int)mysqli_fetch_assoc($rRes)['cnt'];

// Fetch 5 latest movies
$latestMovies = mysqli_query($conn, "SELECT id, original_title, release_date, poster_path FROM movies ORDER BY release_date DESC LIMIT 5");

// Fetch 5 latest users
$latestUsers = mysqli_query($conn, "SELECT id, name, email, contact FROM user ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Movie Recommendation System</title>
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
            display: flex;
            flex-direction: column;
        }

        .admin-container {
            max-width: 1200px;
            width: 100%;
            margin: 32px auto;
            padding: 0 20px;
            flex: 1;
        }

        /* Top Hero Card */
        .admin-hero-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 28px 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .badge-admin {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #f59e0b;
            background-color: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.25);
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .hero-title {
            color: #f8fafc;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }

        .hero-desc {
            color: #94a3b8;
            font-size: 14.5px;
            margin: 0;
        }

        .quick-btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 9px 18px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .quick-btn:hover {
            background-color: #1d4ed8;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: border-color 0.2s ease, transform 0.15s ease;
        }

        .stat-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 26px;
            width: 48px;
            height: 48px;
            background-color: #131924;
            border: 1px solid #242e40;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-value {
            color: #f8fafc;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 2px 0;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
        }

        /* Action Grid */
        .action-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .action-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 12px;
        }

        .action-card h3 {
            color: #f1f5f9;
            font-size: 16px;
            margin: 0;
        }

        .action-card p {
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.45;
            margin: 0;
        }

        .action-link {
            align-self: flex-start;
            color: #60a5fa;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .action-link:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        /* Previews Grid */
        .dash-previews-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .preview-panel {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 24px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #242e40;
        }

        .panel-title {
            color: #f8fafc;
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .panel-viewall {
            color: #60a5fa;
            font-size: 12.5px;
            text-decoration: none;
            font-weight: 500;
        }

        .panel-viewall:hover {
            text-decoration: underline;
        }

        /* Clean Table */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .admin-table th {
            text-align: left;
            padding: 10px 12px;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid #242e40;
            background-color: #131924;
        }

        .admin-table td {
            padding: 10px 12px;
            color: #cbd5e1;
            border-bottom: 1px solid #1e2637;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .thumb-img {
            width: 32px;
            height: 48px;
            object-fit: cover;
            border-radius: 4px;
            display: block;
        }

        footer.admin-footer {
            background-color: #111622;
            border-top: 1px solid #1e2637;
            color: #64748b;
            text-align: center;
            padding: 20px;
            margin-top: 48px;
            font-size: 13px;
        }

        @media (max-width: 840px) {
            .dash-previews-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include("adnav.php"); ?>

<div class="admin-container">
    <!-- Admin Hero Card -->
    <div class="admin-hero-card">
        <div>
            <span class="badge-admin">System Overview</span>
            <h1 class="hero-title">Admin Control Center</h1>
            <p class="hero-desc">Monitor catalog metrics, manage registered user accounts, and update film entries.</p>
        </div>
        <div>
            <a href="nowshowingform.php" class="quick-btn">+ Add New Movie</a>
        </div>
    </div>

    <!-- Metrics Summary -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎬</div>
            <div>
                <div class="stat-value"><?php echo $totalMovies; ?></div>
                <div class="stat-label">Catalog Movies</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div>
                <div class="stat-value"><?php echo $totalRatings; ?></div>
                <div class="stat-label">Ratings Recorded</div>
            </div>
        </div>

    </div>

    <!-- Action Shortcuts -->
    <div class="action-cards-grid">
        <div class="action-card">
            <div>
                <h3>Add New Movie</h3>
                <p>Register a new film with release date, runtime, genres, and poster art.</p>
            </div>
            <a href="nowshowingform.php" class="action-link">Open Movie Creator &rarr;</a>
        </div>

        <div class="action-card">
            <div>
                <h3>Manage Movies</h3>
                <p>Search existing titles, update poster paths, and remove inactive films.</p>
            </div>
            <a href="shownow.php" class="action-link">View Movie Catalog &rarr;</a>
        </div>

        <div class="action-card">
            <div>
                <h3>Manage Users</h3>
                <p>Review registered member details, addresses, contacts, and account statuses.</p>
            </div>
            <a href="shu.php" class="action-link">Manage User Accounts &rarr;</a>
        </div>

    </div>

    <!-- Previews Grid -->
    <div class="dash-previews-grid">
        <!-- Recent Movies -->
        <div class="preview-panel">
            <div class="panel-header">
                <h2 class="panel-title">Recent Movies</h2>
                <a href="shownow.php" class="panel-viewall">View all &rarr;</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Release</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($latestMovies && mysqli_num_rows($latestMovies) > 0): ?>
                        <?php while ($m = mysqli_fetch_assoc($latestMovies)): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($m['poster_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($m['poster_path']); ?>" alt="poster" class="thumb-img">
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($m['original_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['release_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No movies found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Users -->
        <div class="preview-panel">
            <div class="panel-header">
                <h2 class="panel-title">Recent Members</h2>
                <a href="shu.php" class="panel-viewall">View all &rarr;</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($latestUsers && mysqli_num_rows($latestUsers) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($latestUsers)): ?>
                            <tr>
                                <td>#<?php echo $u['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No registered users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="admin-footer">
    <p>&copy; <?php echo date('Y'); ?> Movie Recommendation System &bull; Admin Control Panel</p>
</footer>

<?php if (isset($_GET['movie_added']) && $_GET['movie_added'] == '1'): ?>
<!-- Movie Added Success Popup -->
<div id="movieAddedOverlay" class="success-overlay" role="dialog" aria-modal="true" aria-label="Movie Added Successfully">
    <div class="success-popup">
        <div class="success-icon-ring">
            <svg class="success-check" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle class="check-circle" cx="26" cy="26" r="24" stroke="#22c55e" stroke-width="2.5" fill="none"/>
                <path class="check-tick" d="M14 26 L22 34 L38 18" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
        </div>
        <h2 class="success-title">Movie Added!</h2>
        <p class="success-message">The movie has been successfully added to the catalog.</p>
        <button class="success-btn" onclick="closeMovieAddedPopup()">Got it</button>
        <div class="success-progress-bar"><div class="success-progress-fill" id="successProgressFill"></div></div>
    </div>
</div>
<style>
    .success-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: overlayFadeIn 0.3s ease;
    }
    @keyframes overlayFadeIn {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    .success-popup {
        background: linear-gradient(145deg, #1a2438, #1e2d42);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 16px;
        padding: 40px 36px 28px;
        text-align: center;
        max-width: 380px;
        width: 90%;
        box-shadow: 0 0 60px rgba(34, 197, 94, 0.15), 0 20px 60px rgba(0,0,0,0.5);
        animation: popupSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        overflow: hidden;
    }
    @keyframes popupSlideIn {
        from { transform: scale(0.75) translateY(20px); opacity: 0; }
        to   { transform: scale(1) translateY(0);       opacity: 1; }
    }
    .success-icon-ring {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        filter: drop-shadow(0 0 14px rgba(34, 197, 94, 0.5));
    }
    .check-circle {
        stroke-dasharray: 165;
        stroke-dashoffset: 165;
        animation: drawCircle 0.6s ease 0.2s forwards;
    }
    .check-tick {
        stroke-dasharray: 40;
        stroke-dashoffset: 40;
        animation: drawTick 0.4s ease 0.7s forwards;
    }
    @keyframes drawCircle {
        to { stroke-dashoffset: 0; }
    }
    @keyframes drawTick {
        to { stroke-dashoffset: 0; }
    }
    .success-title {
        color: #f0fdf4;
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 10px;
        letter-spacing: -0.01em;
    }
    .success-message {
        color: #94a3b8;
        font-size: 14px;
        margin: 0 0 24px;
        line-height: 1.6;
    }
    .success-btn {
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: #fff;
        border: none;
        padding: 11px 32px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
    }
    .success-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(34, 197, 94, 0.45);
    }
    .success-progress-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: rgba(34, 197, 94, 0.15);
        border-radius: 0 0 16px 16px;
    }
    .success-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #16a34a, #22c55e);
        border-radius: 0 0 16px 16px;
        width: 100%;
        animation: shrinkProgress 4s linear forwards;
    }
    @keyframes shrinkProgress {
        from { width: 100%; }
        to   { width: 0%;   }
    }
    .success-overlay.fade-out {
        animation: overlayFadeOut 0.4s ease forwards;
    }
    @keyframes overlayFadeOut {
        to { opacity: 0; pointer-events: none; }
    }
</style>
<script>
    function closeMovieAddedPopup() {
        var overlay = document.getElementById('movieAddedOverlay');
        overlay.classList.add('fade-out');
        setTimeout(function() { overlay.remove(); }, 400);
        // Clean up URL so refresh doesn't re-show popup
        if (window.history.replaceState) {
            window.history.replaceState(null, '', 'admin.php');
        }
    }
    // Auto-dismiss after 4 seconds
    setTimeout(closeMovieAddedPopup, 4000);
</script>
<?php endif; ?>

</body>
</html>
