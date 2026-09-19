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
$totalBookings = 0;

$mRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM movies");
if ($mRes) $totalMovies = (int)mysqli_fetch_assoc($mRes)['cnt'];

$uRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM user");
if ($uRes) $totalUsers = (int)mysqli_fetch_assoc($uRes)['cnt'];

$rRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM watched_movies WHERE rating IS NOT NULL");
if ($rRes) $totalRatings = (int)mysqli_fetch_assoc($rRes)['cnt'];

$tableCheck = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings'");
$bookingsTableExists = ($tableCheck && (int)mysqli_fetch_assoc($tableCheck)['cnt'] > 0);
if ($bookingsTableExists) {
    $bRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bookings");
    if ($bRes) $totalBookings = (int)mysqli_fetch_assoc($bRes)['cnt'];
}

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

        <div class="stat-card">
            <div class="stat-icon">🎟️</div>
            <div>
                <div class="stat-value"><?php echo $totalBookings; ?></div>
                <div class="stat-label">Total Bookings</div>
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

        <div class="action-card">
            <div>
                <h3>Manage Bookings</h3>
                <p>Inspect ticket reservations, seat allocations, and order totals.</p>
            </div>
            <a href="show.php" class="action-link">Inspect Bookings &rarr;</a>
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

</body>
</html>