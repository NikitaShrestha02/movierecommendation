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

// Watched films, newest first -- this panel moved here from watched.php,
// which is now the recommendations page.
$watchedMovies = [];
$watchedStmt = $conn->prepare(
    "SELECT m.id, m.original_title, m.poster_path, m.genres, m.release_date,
            wm.rating, wm.watched_at
       FROM watched_movies wm
       JOIN movies m ON wm.movies_id = m.id
      WHERE wm.user_id = ?
   ORDER BY wm.watched_at DESC, m.release_date DESC"
);
if ($watchedStmt) {
    $watchedStmt->bind_param("i", $userId);
    $watchedStmt->execute();
    $watchedRes = $watchedStmt->get_result();
    while ($row = $watchedRes->fetch_assoc()) {
        $watchedMovies[] = $row;
    }
    $watchedStmt->close();
}

// Parse preferred genres
$preferredGenresList = [];
if (!empty($preferredGenresRaw)) {
    $preferredGenresList = array_map('trim', explode(',', $preferredGenresRaw));
    $preferredGenresList = array_values(array_filter($preferredGenresList, fn($g) => $g !== ''));
}

// ---------------------------------------------------------------
// Derived analytics -- computed from data already fetched above, so
// no extra queries. These power the "taste profile" panel.
// ---------------------------------------------------------------

/** Parse a movies.genres cell, which is either raw TMDB JSON or plain
 *  comma text, into a list of display names. */
function ud_parse_genres($raw): array {
    $raw = trim((string)$raw);
    if ($raw === '') return [];
    $names = [];
    if ($raw[0] === '[' || $raw[0] === '{') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item) && isset($item['name'])) $names[] = (string)$item['name'];
                elseif (is_string($item)) $names[] = $item;
            }
        }
    }
    if (!$names) $names = preg_split('/\s*[,|;]\s*/', $raw) ?: [];
    $out = [];
    foreach ($names as $n) {
        $n = trim($n);
        if ($n !== '') $out[] = $n;
    }
    return $out;
}

// Genre frequency across watched films.
$genreCounts = [];   // key => ['label' => Display, 'count' => n]
foreach ($watchedMovies as $wm) {
    foreach (ud_parse_genres($wm['genres'] ?? '') as $g) {
        $key = strtolower($g);
        if (!isset($genreCounts[$key])) {
            $genreCounts[$key] = ['label' => ucwords($key), 'count' => 0];
        }
        $genreCounts[$key]['count']++;
    }
}
uasort($genreCounts, fn($a, $b) => $b['count'] <=> $a['count']);
$topGenres  = array_slice(array_values($genreCounts), 0, 6);
$genreMax   = $topGenres ? max(array_column($topGenres, 'count')) : 0;
$favGenre   = $topGenres[0]['label'] ?? null;

// Rating distribution (1..5) across rated films.
$ratingDist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($watchedMovies as $wm) {
    $r = (int)($wm['rating'] ?? 0);
    if ($r >= 1 && $r <= 5) $ratingDist[$r]++;
}
$ratingMax = $ratingDist ? max($ratingDist) : 0;

$ratedPct  = $totalWatched > 0 ? (int)round(($totalRated / $totalWatched) * 100) : 0;

// Most recent activity date.
$lastWatchedLabel = '';
if (!empty($watchedMovies[0]['watched_at'])) {
    $ts = strtotime($watchedMovies[0]['watched_at']);
    if ($ts) $lastWatchedLabel = date('M j, Y', $ts);
}

// Current mood (set by the recommender / mood modal).
$currentMood = isset($_SESSION['user_mood']) && $_SESSION['user_mood'] !== ''
    ? ucfirst((string)$_SESSION['user_mood'])
    : '';

// Avatar initials from the user's name (falls back to email).
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim((string)$userName)) ?: [], 0, 2) as $p) {
    if ($p !== '') $initials .= strtoupper($p[0]);
}
if ($initials === '') $initials = strtoupper(substr($loggedInEmail, 0, 1));

// Rounded average, for drawing a star row.
$avgStars = (int)round($avgRating);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg: #0f141c;
            --surface: #181f2c;
            --surface-2: #131924;
            --border: #242e40;
            --border-2: #2d384c;
            --text: #cbd5e1;
            --text-dim: #94a3b8;
            --text-mute: #64748b;
            --heading: #f8fafc;
            --accent: #3b82f6;
            --accent-strong: #2563eb;
            --amber: #f59e0b;
            --green: #34d399;
            --radius: 10px;
            --radius-sm: 6px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-container {
            max-width: 1080px;
            width: 100%;
            margin: 32px auto;
            padding: 0 20px;
            flex: 1;
        }

        /* ---- Hero ------------------------------------------------ */
        .hero {
            position: relative;
            background:
                radial-gradient(1200px 200px at 0% 0%, rgba(37, 99, 235, 0.16), transparent 60%),
                var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 26px 28px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 22px;
            overflow: hidden;
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-strong), #7c3aed 60%, transparent);
        }

        .hero-identity {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .avatar {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
            letter-spacing: 0.5px;
        }

        .hero-name {
            color: var(--heading);
            font-size: 23px;
            font-weight: 700;
            margin: 0 0 3px 0;
            letter-spacing: -0.01em;
        }
        .hero-email {
            color: var(--text-dim);
            font-size: 13.5px;
            margin: 0 0 10px 0;
            word-break: break-all;
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .chip {
            background-color: var(--surface-2);
            border: 1px solid var(--border);
            color: var(--text-dim);
            font-size: 11.5px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .chip-mood {
            color: var(--amber);
            border-color: rgba(245, 158, 11, 0.35);
            background-color: rgba(245, 158, 11, 0.10);
        }
        .chip-label {
            color: var(--text-mute);
            font-size: 11px;
            margin-right: 2px;
        }

        .hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* ---- Buttons (also used by included upprof.php) --------- */
        .btn {
            display: inline-block;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
            font-family: inherit;
            text-align: center;
        }
        .btn-primary { background-color: var(--accent-strong); color: #fff; border-color: var(--accent-strong); }
        .btn-primary:hover { background-color: #1d4ed8; border-color: #1d4ed8; }
        .btn-secondary { background-color: #1e2637; color: var(--text); border-color: var(--border-2); }
        .btn-secondary:hover { background-color: #283449; color: #fff; }
        .btn-ghost { background: transparent; color: var(--text-dim); border-color: var(--border-2); }
        .btn-ghost:hover { color: #fff; border-color: var(--accent); }
        .btn-danger { background-color: rgba(239, 68, 68, 0.12); color: #f87171; border-color: rgba(239, 68, 68, 0.3); }
        .btn-danger:hover { background-color: #dc2626; color: #fff; border-color: #dc2626; }
        .btn-block { width: 100%; display: block; }

        /* ---- Stats ---------------------------------------------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }
        .stat-card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            font-size: 22px;
            width: 44px;
            height: 44px;
            background-color: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-value {
            color: var(--heading);
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 2px 0;
            line-height: 1.1;
        }
        .stat-value .stat-suffix { color: var(--text-mute); font-size: 13px; font-weight: 600; }
        .stat-label { color: var(--text-dim); font-size: 12.5px; margin: 0; }
        .stat-stars { color: var(--amber); font-size: 13px; letter-spacing: 1px; }
        .stat-stars .off { color: #3a4counterfeit; }

        /* ---- Panels --------------------------------------------- */
        .panel {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px 26px;
        }
        .panel-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            padding-bottom: 13px;
            border-bottom: 1px solid var(--border);
        }
        .panel-title { color: var(--heading); font-size: 16.5px; font-weight: 700; margin: 0; }
        .panel-badge {
            font-size: 11px;
            font-weight: 600;
            color: #60a5fa;
            background-color: rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.25);
            padding: 3px 9px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .panel + .panel { margin-top: 20px; }

        /* ---- Taste profile (the feature) ------------------------ */
        .taste-panel { margin-bottom: 22px; }
        .taste-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 34px;
        }
        .taste-sub-title {
            color: var(--text-dim);
            font-size: 12.5px;
            font-weight: 600;
            margin: 0 0 14px 0;
        }

        /* Genre bars */
        .genre-bar-row {
            display: grid;
            grid-template-columns: 108px 1fr 34px;
            align-items: center;
            gap: 12px;
            margin-bottom: 11px;
        }
        .genre-bar-name {
            color: var(--text);
            font-size: 13px;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .genre-bar-track {
            background-color: var(--surface-2);
            border-radius: 999px;
            height: 10px;
            overflow: hidden;
        }
        .genre-bar-fill {
            height: 100%;
            width: 0;
            border-radius: 999px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            transition: width 0.9s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .genre-bar-count { color: var(--text-dim); font-size: 12px; text-align: left; }

        /* Rating histogram */
        .rating-hist {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 128px;
            padding-top: 6px;
        }
        .rating-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 7px; height: 100%; justify-content: flex-end; }
        .rating-col-track { width: 100%; display: flex; align-items: flex-end; justify-content: center; flex: 1; }
        .rating-col-fill {
            width: 70%;
            max-width: 34px;
            height: 0;
            border-radius: 5px 5px 0 0;
            background: linear-gradient(180deg, #fbbf24, #f59e0b);
            transition: height 0.9s cubic-bezier(0.22, 1, 0.36, 1);
            min-height: 2px;
        }
        .rating-col-fill.empty { background: var(--surface-2); }
        .rating-col-label { color: var(--amber); font-size: 12px; font-weight: 600; }
        .rating-col-n { color: var(--text-mute); font-size: 11px; }

        .taste-empty { color: var(--text-dim); font-size: 13px; line-height: 1.6; }

        /* ---- Main grid: profile + sidebar ----------------------- */
        .main-grid {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 22px;
            align-items: start;
            margin-bottom: 22px;
        }
        .side-column { display: flex; flex-direction: column; gap: 20px; }

        .side-block { }
        .side-block-title { color: var(--heading); font-size: 14px; font-weight: 600; margin: 0 0 5px 0; }
        .side-block-desc { color: var(--text-dim); font-size: 12.5px; margin: 0 0 12px 0; line-height: 1.5; }

        /* ---- Profile form (classes required by upprof.php) ------ */
        .profile-form-wrapper {}
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 16px; }
        .form-row .form-group { margin-bottom: 0; }
        .dash-label { color: var(--text); font-size: 13px; font-weight: 600; margin-bottom: 7px; }
        .dash-input {
            width: 100%;
            background-color: var(--surface-2);
            border: 1px solid var(--border-2);
            border-radius: var(--radius-sm);
            color: var(--heading);
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .dash-input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
        .form-actions { margin-top: 20px; display: flex; justify-content: flex-end; }

        .dash-alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13.5px; margin-bottom: 20px; line-height: 1.5; }
        .dash-alert-success { background-color: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: var(--green); }
        .dash-alert-error { background-color: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; }

        /* ---- Watched films -------------------------------------- */
        .watched-panel { margin-bottom: 8px; }
        .watched-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 16px;
        }
        .watched-card {
            background-color: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px;
            text-decoration: none;
            display: block;
            transition: transform 0.18s ease, border-color 0.18s ease;
        }
        .watched-card:hover { transform: translateY(-3px); border-color: var(--accent); }
        .watched-poster { position: relative; }
        .watched-card img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            border-radius: 5px;
            display: block;
            background-color: var(--bg);
        }
        .poster-rating {
            position: absolute;
            top: 7px;
            left: 7px;
            background: rgba(9, 12, 20, 0.85);
            color: var(--amber);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 7px;
            border-radius: 999px;
            backdrop-filter: blur(2px);
        }
        .poster-rating.unrated { color: var(--text-mute); font-weight: 500; }
        .watched-card-title { color: #f1f5f9; font-size: 12.5px; font-weight: 600; margin-top: 8px; line-height: 1.3; }
        .watched-card-meta { color: var(--text-mute); font-size: 11px; margin-top: 3px; }

        .watched-hidden { display: none; }
        .watched-more-wrap { text-align: center; margin-top: 20px; }

        .empty-state { text-align: center; padding: 34px 20px; color: var(--text-dim); font-size: 13.5px; line-height: 1.6; }
        .empty-state strong { display: block; color: #e2e8f0; font-size: 15px; margin-bottom: 6px; }

        @media (max-width: 880px) {
            .main-grid { grid-template-columns: 1fr; }
            .taste-grid { grid-template-columns: 1fr; gap: 26px; }
            .hero { flex-direction: column; align-items: flex-start; }
            .hero-actions { width: 100%; }
            .form-row { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            .genre-bar-fill, .rating-col-fill { transition: none; }
        }
    </style>
    <title>Your Dashboard - Movie Recommendation</title>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">

    <!-- Hero -->
    <section class="hero">
        <div class="hero-identity">
            <div class="avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></div>
            <div>
                <h1 class="hero-name">Welcome back, <?php echo htmlspecialchars($userName); ?></h1>
                <p class="hero-email"><?php echo htmlspecialchars($loggedInEmail); ?></p>
                <div class="chip-row">
                    <?php if ($currentMood !== ''): ?>
                        <span class="chip chip-mood">Mood: <?php echo htmlspecialchars($currentMood); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($preferredGenresList)): ?>
                        <span class="chip-label">Likes</span>
                        <?php foreach ($preferredGenresList as $genre): ?>
                            <span class="chip"><?php echo htmlspecialchars($genre); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="chip-label">No preferred genres set yet</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="hero-actions">
            <button type="button" id="openMoodModalBtn" class="btn btn-ghost">Update mood</button>
            <a href="index.php" class="btn btn-secondary">Browse movies</a>
            <a href="watched.php" class="btn btn-primary">My recommendations</a>
        </div>
    </section>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🎬</div>
            <div>
                <div class="stat-value"><?php echo $totalWatched; ?></div>
                <div class="stat-label">Films watched</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div>
                <div class="stat-value"><?php echo $totalRated; ?> <span class="stat-suffix">of <?php echo $totalWatched; ?> rated</span></div>
                <div class="stat-label"><?php echo $ratedPct; ?>% of your library</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div>
                <?php if ($avgRating > 0): ?>
                    <div class="stat-value"><?php echo $avgRating; ?><span class="stat-suffix"> / 5</span></div>
                    <div class="stat-stars" aria-hidden="true"><?php
                        echo str_repeat('&#9733;', $avgStars) . str_repeat('&#9734;', 5 - $avgStars);
                    ?></div>
                <?php else: ?>
                    <div class="stat-value">—</div>
                    <div class="stat-label">Rate a film to begin</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎭</div>
            <div>
                <div class="stat-value" style="font-size:<?php echo $favGenre ? '17px' : '20px'; ?>;"><?php echo $favGenre ? htmlspecialchars($favGenre) : '—'; ?></div>
                <div class="stat-label">Your top genre</div>
            </div>
        </div>
    </div>

    <!-- Taste profile: the feature panel -->
    <section class="panel taste-panel">
        <div class="panel-header">
            <h2 class="panel-title">Your taste profile</h2>
            <span class="panel-badge">Powers your KNN picks</span>
        </div>

        <?php if ($totalWatched === 0): ?>
            <p class="taste-empty">
                Once you mark a few films as watched, this is where you'll see the genres you
                gravitate toward and how you tend to rate. That same profile is what the
                recommender uses to find your next film.
            </p>
        <?php else: ?>
            <div class="taste-grid">
                <!-- Genre breakdown -->
                <div>
                    <p class="taste-sub-title">Most-watched genres</p>
                    <?php if ($topGenres): ?>
                        <?php foreach ($topGenres as $g): ?>
                            <?php $pct = $genreMax > 0 ? ($g['count'] / $genreMax) * 100 : 0; ?>
                            <div class="genre-bar-row">
                                <span class="genre-bar-name" title="<?php echo htmlspecialchars($g['label']); ?>"><?php echo htmlspecialchars($g['label']); ?></span>
                                <span class="genre-bar-track">
                                    <span class="genre-bar-fill" data-width="<?php echo round($pct, 1); ?>"></span>
                                </span>
                                <span class="genre-bar-count"><?php echo (int)$g['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="taste-empty">Your watched films don't have genre data yet.</p>
                    <?php endif; ?>
                </div>

                <!-- Rating distribution -->
                <div>
                    <p class="taste-sub-title">How you rate</p>
                    <?php if ($totalRated > 0): ?>
                        <div class="rating-hist">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <?php
                                    $n = $ratingDist[$s];
                                    $h = $ratingMax > 0 ? ($n / $ratingMax) * 100 : 0;
                                ?>
                                <div class="rating-col">
                                    <div class="rating-col-track">
                                        <span class="rating-col-fill<?php echo $n === 0 ? ' empty' : ''; ?>" data-height="<?php echo round($h, 1); ?>"></span>
                                    </div>
                                    <span class="rating-col-n"><?php echo $n; ?></span>
                                    <span class="rating-col-label"><?php echo $s; ?>&#9733;</span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php else: ?>
                        <p class="taste-empty">
                            You've marked films as watched but haven't rated them.
                            Rating tells the recommender what you actually enjoyed.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Profile + sidebar -->
    <div class="main-grid">
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Profile information</h2>
                <span class="panel-badge">Account</span>
            </div>
            <?php include("upprof.php"); ?>
        </div>

        <div class="side-column">
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">Password</h3>
                </div>
                <div class="side-block">
                    <p class="side-block-desc">Update your password periodically to keep your account and preferences secure.</p>
                    <a href="changepassword.php" class="btn btn-secondary btn-block">Change password</a>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">Session</h3>
                </div>
                <div class="side-block">
                    <?php if ($lastWatchedLabel !== ''): ?>
                        <p class="side-block-desc">Last film added on <?php echo htmlspecialchars($lastWatchedLabel); ?>. Signed in as <?php echo htmlspecialchars($userName); ?>.</p>
                    <?php else: ?>
                        <p class="side-block-desc">Sign out securely. You can sign back in any time.</p>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger btn-block">Log out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Watched films -->
    <section class="panel watched-panel">
        <div class="panel-header">
            <h2 class="panel-title">Your watched films</h2>
            <span class="panel-badge"><?php echo $totalWatched; ?> total</span>
        </div>

        <?php if (empty($watchedMovies)): ?>
            <div class="empty-state">
                <strong>No films in your library yet</strong>
                Browse the catalogue and mark films you've seen — each one feeds the
                recommendation engine, and rating them makes it sharper still.
                <div style="margin-top:16px;">
                    <a href="index.php" class="btn btn-primary">Browse movies</a>
                </div>
            </div>
        <?php else: ?>
            <?php $visibleLimit = 12; ?>
            <div class="watched-grid" id="watchedGrid">
                <?php foreach ($watchedMovies as $i => $movie): ?>
                    <?php
                        $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : 'default.jpg';
                        $year   = !empty($movie['release_date']) ? substr($movie['release_date'], 0, 4) : '';
                        $rating = (int)($movie['rating'] ?? 0);
                        $hiddenClass = $i >= $visibleLimit ? ' watched-hidden' : '';
                    ?>
                    <a href="details.php?id=<?php echo (int)$movie['id']; ?>"
                       class="watched-card<?php echo $hiddenClass; ?>"
                       <?php echo $i >= $visibleLimit ? 'data-extra="1"' : ''; ?>>
                        <div class="watched-poster">
                            <img src="<?php echo htmlspecialchars($poster); ?>"
                                 alt="<?php echo htmlspecialchars($movie['original_title']); ?>"
                                 onerror="this.onerror=null;this.src='default.jpg';">
                            <?php if ($rating > 0): ?>
                                <span class="poster-rating" title="You rated this <?php echo $rating; ?> / 5">&#9733; <?php echo $rating; ?></span>
                            <?php else: ?>
                                <span class="poster-rating unrated" title="Not rated yet">Unrated</span>
                            <?php endif; ?>
                        </div>
                        <div class="watched-card-title"><?php echo htmlspecialchars($movie['original_title']); ?></div>
                        <?php if ($year !== ''): ?>
                            <div class="watched-card-meta"><?php echo htmlspecialchars($year); ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (count($watchedMovies) > $visibleLimit): ?>
                <div class="watched-more-wrap">
                    <button type="button" id="watchedMoreBtn" class="btn btn-secondary"
                            data-remaining="<?php echo count($watchedMovies) - $visibleLimit; ?>">
                        Show all <?php echo count($watchedMovies); ?> films
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>

<?php include("footer.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Reveal-on-load for the taste bars (a single, deliberate moment).
    requestAnimationFrame(function () {
        document.querySelectorAll(".genre-bar-fill").forEach(function (el) {
            el.style.width = (el.dataset.width || 0) + "%";
        });
        document.querySelectorAll(".rating-col-fill").forEach(function (el) {
            el.style.height = (el.dataset.height || 0) + "%";
        });
    });

    // "Show all" for the watched grid.
    var moreBtn = document.getElementById("watchedMoreBtn");
    if (moreBtn) {
        moreBtn.addEventListener("click", function () {
            document.querySelectorAll('#watchedGrid [data-extra="1"]').forEach(function (el) {
                el.classList.remove("watched-hidden");
            });
            moreBtn.parentElement.remove();
        });
    }
});
</script>

</body>
</html>