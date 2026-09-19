<?php
session_start();
if (!isset($_COOKIE['uemail'])) {
    header('location: nlogin.php');
    die();
}

if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}

include('connection.php');

$loggedInEmail = $_SESSION['uemail'];

// Fetch current user record
$userStmt = $conn->prepare("SELECT id, name, email, address, contact, preferred_genres FROM user WHERE email = ?");
$userStmt->bind_param("s", $loggedInEmail);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult && $userResult->num_rows > 0) {
    $userRow = $userResult->fetch_assoc();
    $userId = (int)$userRow['id'];
    $userName = $userRow['name'];
    $userAddress = $userRow['address'];
    $userContact = $userRow['contact'];
    $preferredGenresRaw = $userRow['preferred_genres'] ?? '';
} else {
    header("location: nlogin.php");
    exit();
}

// Fetch stats for watched movies and ratings
$totalWatched = 0;
$totalRated = 0;
$avgRating = 0;

$statsStmt = $conn->prepare("SELECT COUNT(*) AS total_watched, COUNT(rating) AS total_rated, AVG(rating) AS avg_rating FROM watched_movies WHERE user_id = ?");
if ($statsStmt) {
    $statsStmt->bind_param("i", $userId);
    $statsStmt->execute();
    $statsData = $statsStmt->get_result()->fetch_assoc();
    if ($statsData) {
        $totalWatched = (int)$statsData['total_watched'];
        $totalRated = (int)$statsData['total_rated'];
        $avgRating = $statsData['avg_rating'] !== null ? round((float)$statsData['avg_rating'], 1) : 0;
    }
}

// Parse preferred genres
$preferredGenresList = [];
if (!empty($preferredGenresRaw)) {
    $preferredGenresList = array_map('trim', explode(',', $preferredGenresRaw));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .page-container {
            max-width: 1050px;
            width: 100%;
            margin: 36px auto;
            padding: 0 20px;
            flex: 1;
        }

        /* User Hero Card */
        .user-hero-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar-wrapper img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #334155;
            display: block;
        }

        .user-meta h1 {
            color: #f8fafc;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }

        .user-meta .user-email {
            color: #94a3b8;
            font-size: 14px;
            margin: 0 0 10px 0;
        }

        .genre-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .genre-tag {
            background-color: #131924;
            border: 1px solid #263347;
            color: #94a3b8;
            font-size: 11.5px;
            font-weight: 500;
            padding: 3px 9px;
            border-radius: 4px;
        }

        .hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Dashboard Metrics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 20px;
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
            font-size: 24px;
            width: 44px;
            height: 44px;
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
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 2px 0;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 12.5px;
            margin: 0;
        }

        /* Dashboard Main Grid (2-Columns) */
        .dash-layout-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .dash-panel {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 28px;
            margin-bottom: 24px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #242e40;
        }

        .panel-title {
            color: #f8fafc;
            font-size: 17px;
            font-weight: 700;
            margin: 0;
        }

        .panel-badge {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #60a5fa;
            background-color: rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.25);
            padding: 3px 8px;
            border-radius: 4px;
        }

        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 16px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .dash-label {
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .dash-input {
            width: 100%;
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .dash-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        /* Alerts */
        .dash-alert {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13.5px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .dash-alert-success {
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
        }

        .dash-alert-error {
            background-color: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 5px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            font-family: inherit;
            text-align: center;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #1e2637;
            color: #cbd5e1;
            border: 1px solid #2d384c;
        }

        .btn-secondary:hover {
            background-color: #283449;
            color: #ffffff;
        }

        .btn-danger {
            background-color: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        .btn-block {
            width: 100%;
            display: block;
        }

        /* Side Action List */
        .action-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .action-card-item {
            background-color: #131924;
            border: 1px solid #242e40;
            border-radius: 6px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-item-title {
            color: #f1f5f9;
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }

        .action-item-desc {
            color: #94a3b8;
            font-size: 12.5px;
            margin: 0 0 4px 0;
            line-height: 1.4;
        }

        @media (max-width: 840px) {
            .dash-layout-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .user-hero-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .hero-actions {
                width: 100%;
            }
        }
    </style>
    <title>User Dashboard - Movie Recommendation</title>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <!-- User Hero Card -->
    <div class="user-hero-card">
        <div class="user-profile-info">
            <div class="user-avatar-wrapper">
                <img src="userr.jpg" alt="<?php echo htmlspecialchars($userName); ?>">
            </div>
            <div class="user-meta">
                <h1>Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                <p class="user-email"><?php echo htmlspecialchars($loggedInEmail); ?></p>
                <?php if (!empty($preferredGenresList)): ?>
                    <div class="genre-tags">
                        <?php foreach ($preferredGenresList as $genre): ?>
                            <span class="genre-tag"><?php echo htmlspecialchars($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-actions">
            <a href="watched.php" class="btn btn-secondary">Watched Movies</a>
            <a href="index.php" class="btn btn-primary">Recommendations</a>
        </div>
    </div>

    <!-- Dashboard Metrics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎬</div>
            <div>
                <div class="stat-value"><?php echo $totalWatched; ?></div>
                <div class="stat-label">Watched Movies</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div>
                <div class="stat-value"><?php echo $totalRated; ?></div>
                <div class="stat-label">Rated Movies</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div>
                <div class="stat-value"><?php echo ($avgRating > 0) ? $avgRating . ' / 5' : 'None yet'; ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div>
                <div class="stat-value">KNN Engine</div>
                <div class="stat-label">Active &amp; Calibrated</div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dash-layout-grid">
        <!-- Profile Settings Panel -->
        <div class="dash-panel">
            <div class="panel-header">
                <h2 class="panel-title">Profile Information</h2>
                <span class="panel-badge">Account Details</span>
            </div>

            <?php include("upprof.php"); ?>
        </div>

        <!-- Right Side Management Panels -->
        <div class="side-column">
            <!-- Account Security Panel -->
            <div class="dash-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Account Security</h3>
                    <span class="panel-badge">Password</span>
                </div>
                <div class="action-list">
                    <div class="action-card-item">
                        <h4 class="action-item-title">Security Credentials</h4>
                        <p class="action-item-desc">Regularly updating your password helps keep your account and preferences secure.</p>
                        <a href="changepassword.php" class="btn btn-secondary">Change Password</a>
                    </div>
                </div>
            </div>

            <!-- Activity & Sign Out Panel -->
            <div class="dash-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Session Management</h3>
                </div>
                <div class="action-list">
                    <div class="action-card-item">
                        <h4 class="action-item-title">Sign Out</h4>
                        <p class="action-item-desc">End your active session securely. You can sign back in at any time.</p>
                        <a href="logout.php" class="btn btn-danger btn-block">Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

</body>
</html>

