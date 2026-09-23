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

// ── DELETE ──────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $remove_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM `watched_movies` WHERE movies_id = $remove_id");
    mysqli_query($conn, "DELETE FROM `movies` WHERE id = $remove_id");
    header('Location: shownow.php');    
    exit;
}

// ── UPDATE (all columns) ─────────────────────────────────────────────────────
$updateSuccess = false;
if (isset($_POST['update_movie'])) {
    $update_id         = (int)$_POST['movie_id'];
    $original_language = trim($_POST['original_language']);
    $original_title    = trim($_POST['original_title']);
    $overview          = trim($_POST['overview']);
    $release_date      = $_POST['release_date'];
    $runtime           = (int)$_POST['runtime'];
    $status            = trim($_POST['status']);
    $tagline           = trim($_POST['tagline']);
    $genres            = trim($_POST['genres']);
    $keywords          = trim($_POST['keywords']);
    $poster_path       = $_POST['current_poster_path'];

    // Handle optional new poster upload
    if (isset($_FILES['poster_path']) && $_FILES['poster_path']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name   = time() . '_' . basename($_FILES['poster_path']['name']);
        $upload_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['poster_path']['tmp_name'], $upload_path)) {
            $poster_path = $upload_path;
        }
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE `movies` SET
            original_language = ?,
            original_title    = ?,
            overview          = ?,
            release_date      = ?,
            runtime           = ?,
            status            = ?,
            tagline           = ?,
            genres            = ?,
            keywords          = ?,
            poster_path       = ?
         WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssssisssssi",
        $original_language, $original_title, $overview,
        $release_date, $runtime, $status, $tagline,
        $genres, $keywords, $poster_path, $update_id
    );
    if (mysqli_stmt_execute($stmt)) {
        $updateSuccess = true;
    }
    mysqli_stmt_close($stmt);
}

// ── FETCH movies (with search) ───────────────────────────────────────────────
$search_term = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search_term = mysqli_real_escape_string($conn, trim($_GET['search']));
    $query = "SELECT * FROM movies WHERE original_title LIKE '%$search_term%' ORDER BY release_date DESC LIMIT 50";
} else {
    $query = "SELECT * FROM movies ORDER BY release_date DESC LIMIT 50";
}
$result = mysqli_query($conn, $query) or die('Query failed');

$movies = [];
while ($row = mysqli_fetch_assoc($result)) {
    $movies[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Admin Panel</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0; padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c;
            color: #cbd5e1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-container {
            max-width: 1200px;
            width: 100%;
            margin: 32px auto;
            padding: 0 20px;
            flex: 1;
        }

        /* ── Card ── */
        .table-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            padding: 28px 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 1px solid #242e40;
            flex-wrap: wrap;
            gap: 14px;
        }

        .card-title {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        /* ── Search ── */
        .search-wrapper { display: flex; align-items: center; gap: 8px; }

        .search-input {
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 8px 14px;
            font-size: 13.5px;
            font-family: inherit;
            outline: none;
            min-width: 230px;
            transition: border-color 0.2s;
        }
        .search-input:focus { border-color: #3b82f6; }

        .btn-search {
            background-color: #2563eb; color: #fff;
            border: 1px solid #2563eb;
            padding: 8px 16px; font-size: 13px; font-weight: 600;
            border-radius: 6px; cursor: pointer; font-family: inherit;
            transition: background-color 0.2s;
        }
        .btn-search:hover { background-color: #1d4ed8; }

        .btn-clear {
            background-color: #1e2637; color: #cbd5e1;
            border: 1px solid #2d384c;
            padding: 8px 12px; font-size: 13px;
            border-radius: 6px; text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn-clear:hover { background-color: #283449; color: #fff; }

        /* ── Alert ── */
        .alert-success {
            background-color: rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.35);
            color: #34d399;
            padding: 10px 14px; border-radius: 6px;
            font-size: 13px; margin-bottom: 20px;
        }

        /* ── Table ── */
        .table-responsive { overflow-x: auto; }

        table.movie-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        table.movie-table th {
            text-align: left; padding: 12px 14px;
            background-color: #131924; color: #94a3b8;
            font-weight: 600; border-bottom: 1px solid #242e40;
            white-space: nowrap;
        }
        table.movie-table td {
            padding: 11px 14px;
            border-bottom: 1px solid #1e2637;
            vertical-align: middle;
        }
        table.movie-table tr:hover td { background-color: rgba(255,255,255,0.02); }

        .movie-thumb {
            width: 44px; height: 64px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #263347;
            display: block;
        }

        /* ── Buttons ── */
        .edit-btn {
            display: inline-block; padding: 6px 13px;
            border-radius: 5px;
            background-color: rgba(37,99,235,0.12);
            color: #60a5fa;
            border: 1px solid rgba(37,99,235,0.3);
            font-size: 12.5px; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: background-color 0.2s, color 0.2s;
            margin-right: 6px;
        }
        .edit-btn:hover { background-color: #2563eb; color: #fff; border-color: #2563eb; }

        .delete-btn {
            display: inline-block; padding: 6px 13px;
            border-radius: 5px;
            background-color: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.3);
            text-decoration: none;
            font-size: 12.5px; font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s;
        }
        .delete-btn:hover { background-color: #dc2626; color: #fff; border-color: #dc2626; }

        .action-cell { white-space: nowrap; }

        /* ── Edit Modal ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; }

        .modal-box {
            background: linear-gradient(145deg, #181f2c, #1a2438);
            border: 1px solid #242e40;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 680px;
            max-height: 92vh;
            overflow-y: auto;
            padding: 28px 28px 22px;
            animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes modalIn {
            from { transform: scale(0.88) translateY(14px); opacity: 0; }
            to   { transform: scale(1) translateY(0);       opacity: 1; }
        }

        .modal-header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid #242e40;
        }
        .modal-title { color: #f8fafc; font-size: 17px; font-weight: 700; margin: 0; }
        .modal-close {
            background: none; border: none;
            color: #94a3b8; font-size: 22px;
            cursor: pointer; padding: 2px 6px; line-height: 1;
        }
        .modal-close:hover { color: #f1f5f9; }

        /* ── Form inside modal ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-group {
            display: flex; flex-direction: column;
            margin-bottom: 16px;
        }
        .form-row .form-group { margin-bottom: 0; }

        .form-label {
            color: #94a3b8; font-size: 12px;
            font-weight: 600; letter-spacing: 0.04em;
            text-transform: uppercase; margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 9px 12px;
            font-size: 13.5px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
        }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .poster-hint { font-size: 11.5px; color: #64748b; margin-top: 5px; }

        .modal-footer {
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px solid #242e40;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-cancel-modal {
            background-color: #1e2637; color: #cbd5e1;
            border: 1px solid #2d384c;
            padding: 9px 20px; font-size: 13px; font-weight: 600;
            border-radius: 6px; cursor: pointer; font-family: inherit;
        }
        .btn-cancel-modal:hover { background-color: #283449; }

        .btn-save {
            background-color: #2563eb; color: #fff;
            border: 1px solid #2563eb;
            padding: 9px 22px; font-size: 13px; font-weight: 600;
            border-radius: 6px; cursor: pointer; font-family: inherit;
            transition: background-color 0.2s;
        }
        .btn-save:hover { background-color: #1d4ed8; }

        @media (max-width: 560px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include("adnav.php"); ?>

<div class="page-container">
    <div class="table-card">

        <div class="card-header">
            <h1 class="card-title">Manage Movies Catalog</h1>
            <form method="get" action="" class="search-wrapper">
                <input type="text" name="search" class="search-input"
                       placeholder="Search by title..."
                       value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="shownow.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($updateSuccess): ?>
            <div class="alert-success">✓ Movie updated successfully!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="movie-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Language</th>
                        <th>Release Date</th>
                        <th>Runtime</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($movies) > 0): ?>
                    <?php foreach ($movies as $m): ?>
                        <tr>
                            <td>#<?php echo $m['id']; ?></td>
                            <td>
                                <?php if (!empty($m['poster_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($m['poster_path']); ?>"
                                         alt="poster" class="movie-thumb">
                                <?php else: ?>
                                    <span style="color:#64748b;font-size:12px;">No poster</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($m['original_title']); ?></strong></td>
                            <td><?php echo htmlspecialchars(strtoupper($m['original_language'])); ?></td>
                            <td><?php echo htmlspecialchars($m['release_date']); ?></td>
                            <td><?php echo $m['runtime'] ? $m['runtime'] . ' min' : '—'; ?></td>
                            <td><?php echo htmlspecialchars($m['status']); ?></td>
                            <td class="action-cell">
                                <button type="button" class="edit-btn"
                                        onclick="openModal(<?php echo (int)$m['id']; ?>)">Edit</button>
                                <a href="shownow.php?delete=<?php echo $m['id']; ?>"
                                   class="delete-btn"
                                   onclick="return confirm('Delete \'<?php echo addslashes(htmlspecialchars($m['original_title'])); ?>\'? This cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:28px;color:#94a3b8;">
                            No movies found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- ── Edit Modals (one per movie row) ───────────────────────────────────── -->
<?php foreach ($movies as $m): ?>
<div class="modal-overlay" id="modal-<?php echo (int)$m['id']; ?>">
    <div class="modal-box">

        <div class="modal-header">
            <h2 class="modal-title">Edit Movie &mdash; #<?php echo (int)$m['id']; ?></h2>
            <button type="button" class="modal-close"
                    onclick="closeModal(<?php echo (int)$m['id']; ?>)">&times;</button>
        </div>

        <form action="shownow.php<?php echo !empty($search_term) ? '?search='.urlencode($search_term) : ''; ?>"
              method="post" enctype="multipart/form-data">
            <input type="hidden" name="movie_id"           value="<?php echo (int)$m['id']; ?>">
            <input type="hidden" name="current_poster_path" value="<?php echo htmlspecialchars($m['poster_path'] ?? ''); ?>">

            <!-- Row 1: Title + Language -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Original Title</label>
                    <input type="text" name="original_title" class="form-control"
                           value="<?php echo htmlspecialchars($m['original_title']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Language Code</label>
                    <input type="text" name="original_language" class="form-control" maxlength="10"
                           value="<?php echo htmlspecialchars($m['original_language']); ?>" required>
                </div>
            </div>

            <!-- Row 2: Release Date + Runtime -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Release Date</label>
                    <input type="date" name="release_date" class="form-control"
                           value="<?php echo htmlspecialchars($m['release_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Runtime (Minutes)</label>
                    <input type="number" name="runtime" class="form-control" min="1"
                           value="<?php echo (int)$m['runtime']; ?>">
                </div>
            </div>

            <!-- Row 3: Status + Tagline -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <input type="text" name="status" class="form-control" maxlength="50"
                           value="<?php echo htmlspecialchars($m['status']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="tagline" class="form-control" maxlength="255"
                           value="<?php echo htmlspecialchars($m['tagline'] ?? ''); ?>">
                </div>
            </div>

            <!-- Row 4: Genres + Keywords -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Genres (comma-separated)</label>
                    <input type="text" name="genres" class="form-control"
                           value="<?php echo htmlspecialchars($m['genres'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Keywords (comma-separated)</label>
                    <input type="text" name="keywords" class="form-control"
                           value="<?php echo htmlspecialchars($m['keywords'] ?? ''); ?>">
                </div>
            </div>

            <!-- Overview -->
            <div class="form-group">
                <label class="form-label">Synopsis / Overview</label>
                <textarea name="overview" class="form-control" rows="4"><?php echo htmlspecialchars($m['overview'] ?? ''); ?></textarea>
            </div>

            <!-- Poster -->
            <div class="form-group">
                <label class="form-label">Poster Image</label>
                <input type="file" name="poster_path" class="form-control" accept=".jpg,.jpeg,.png">
                <div class="poster-hint">Leave blank to keep the current poster.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel-modal"
                        onclick="closeModal(<?php echo (int)$m['id']; ?>)">Cancel</button>
                <input type="submit" name="update_movie" value="Save Changes" class="btn-save">
            </div>
        </form>

    </div>
</div>
<?php endforeach; ?>

<script>
    function openModal(id) {
        document.getElementById('modal-' + id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById('modal-' + id).classList.remove('open');
        document.body.style.overflow = '';
    }
    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target === el) {
                el.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    });
    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(function(el) {
                el.classList.remove('open');
                document.body.style.overflow = '';
            });
        }
    });
</script>

</body>
</html>
