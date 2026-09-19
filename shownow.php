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

// DELETE movie
if (isset($_GET['delete'])) {
    $remove_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM `watched_movies` WHERE movies_id='$remove_id'") or die('Failed to delete related watched movies');
    mysqli_query($conn, "DELETE FROM `movies` WHERE id='$remove_id'") or die('Failed to delete movie');
    header('Location: shownow.php');
    exit;
}

// UPDATE movie
$updateSuccess = false;
if (isset($_POST['update_movie'])) {
    $update_id = (int)$_POST['movie_id'];
    $update_title = trim($_POST['original_title']);
    $update_poster_path = $_POST['current_poster_path'];

    if (isset($_FILES['poster_path']) && $_FILES['poster_path']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['poster_path']['name']);
        $upload_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['poster_path']['tmp_name'], $upload_path)) {
            $update_poster_path = $upload_path;
        }
    }

    $update_query = "UPDATE `movies` SET original_title=?, poster_path=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssi", $update_title, $update_poster_path, $update_id);
    if (mysqli_stmt_execute($stmt)) {
        $updateSuccess = true;
    }
    mysqli_stmt_close($stmt);
}

// Handle search
$search_term = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search_term = mysqli_real_escape_string($conn, trim($_GET['search']));
    $query = "SELECT * FROM movies WHERE original_title LIKE '%$search_term%' ORDER BY release_date DESC LIMIT 20";
} else {
    $query = "SELECT * FROM movies ORDER BY release_date DESC LIMIT 20";
}
$select_movies = mysqli_query($conn, $query) or die('Query failed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Admin Panel</title>
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
            max-width: 1200px;
            width: 100%;
            margin: 32px auto;
            padding: 0 20px;
            flex: 1;
        }

        .table-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 28px 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #242e40;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-title {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-input {
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 8px 14px;
            font-size: 13.5px;
            font-family: inherit;
            outline: none;
            min-width: 240px;
            transition: border-color 0.2s ease;
        }

        .search-input:focus {
            border-color: #3b82f6;
        }

        .btn-search {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: inherit;
        }

        .btn-search:hover {
            background-color: #1d4ed8;
        }

        .btn-clear {
            background-color: #1e2637;
            color: #cbd5e1;
            border: 1px solid #2d384c;
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 6px;
            text-decoration: none;
        }

        .btn-clear:hover {
            background-color: #283449;
            color: #ffffff;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table.movie-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.movie-table th {
            text-align: left;
            padding: 12px 14px;
            background-color: #131924;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid #242e40;
            white-space: nowrap;
        }

        table.movie-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #1e2637;
            vertical-align: middle;
        }

        table.movie-table tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .movie-thumb {
            width: 48px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #263347;
            display: block;
        }

        .delete-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            background-color: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .delete-btn:hover {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        /* Inline Update Form */
        .inline-edit-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .inline-title-input {
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 5px;
            color: #f1f5f9;
            padding: 6px 10px;
            font-size: 13px;
            font-family: inherit;
            width: 220px;
        }

        .inline-title-input:focus {
            border-color: #3b82f6;
            outline: none;
        }

        .inline-file-input {
            font-size: 11.5px;
            color: #94a3b8;
            max-width: 160px;
        }

        .btn-update {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: inherit;
        }

        .btn-update:hover {
            background-color: #1d4ed8;
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
                <input type="text" name="search" class="search-input" placeholder="Search by title..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="shownow.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($updateSuccess): ?>
            <div class="alert-success">Movie details updated successfully!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="movie-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Quick Edit / Update</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($select_movies) > 0): ?>
                    <?php while ($movie = mysqli_fetch_assoc($select_movies)): ?>
                        <tr>
                            <td>#<?php echo $movie['id']; ?></td>
                            <td>
                                <?php if (!empty($movie['poster_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($movie['poster_path']); ?>" alt="poster" class="movie-thumb">
                                <?php else: ?>
                                    <span style="color:#64748b;">No poster</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($movie['original_title']); ?></strong>
                            </td>
                            <td>
                                <form action="" method="post" enctype="multipart/form-data" class="inline-edit-form">
                                    <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                    <input type="hidden" name="current_poster_path" value="<?php echo htmlspecialchars($movie['poster_path']); ?>">
                                    <input type="text" name="original_title" class="inline-title-input" value="<?php echo htmlspecialchars($movie['original_title']); ?>" required>
                                    <input type="file" name="poster_path" class="inline-file-input" accept=".jpg, .jpeg, .png">
                                    <input type="submit" name="update_movie" value="Save" class="btn-update">
                                </form>
                            </td>
                            <td>
                                <a href="shownow.php?delete=<?php echo $movie['id']; ?>" class="delete-btn"
                                   onclick="return confirm('Are you sure you want to delete this movie?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 24px; color: #94a3b8;">
                            No movies found matching your search.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

