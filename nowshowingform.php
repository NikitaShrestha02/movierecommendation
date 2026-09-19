<?php
session_start();
if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}
if (!isset($_SESSION['uemail']) || $_SESSION['uemail'] !== 'snadmin@gmail.com') {
    header("Location: adform.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Movie - Admin Panel</title>
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
            max-width: 880px;
            width: 100%;
            margin: 36px auto;
            padding: 0 20px;
            flex: 1;
        }

        .form-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 32px 30px;
        }

        .form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #242e40;
            flex-wrap: wrap;
            gap: 12px;
        }

        .form-title {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .badge-admin {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #f59e0b;
            background-color: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.25);
            padding: 4px 10px;
            border-radius: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 18px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .form-label {
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control {
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

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .file-input-wrapper {
            background-color: #131924;
            border: 1px dashed #2d384c;
            border-radius: 6px;
            padding: 14px;
            transition: border-color 0.2s ease;
        }

        .file-input-wrapper:hover {
            border-color: #3b82f6;
        }

        .btn-submit {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 11px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: inherit;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
        }

        .form-actions {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #242e40;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .back-link {
            color: #94a3b8;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #f1f5f9;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-card {
                padding: 22px 18px;
            }
        }
    </style>
</head>
<body>

<?php include("adnav.php"); ?>

<div class="page-container">
    <div class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">Add New Movie</h1>
            </div>
            <span class="badge-admin">Catalog Management</span>
        </div>

        <form action="add_movie.php" method="post" autocomplete="off" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="id" class="form-label">Movie ID</label>
                    <input type="number" name="id" id="id" class="form-control" placeholder="Unique numeric ID" required>
                </div>

                <div class="form-group">
                    <label for="original_title" class="form-label">Original Title</label>
                    <input type="text" id="original_title" name="original_title" class="form-control" maxlength="255" placeholder="e.g. Inception" required />
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="original_language" class="form-label">Language Code</label>
                    <input type="text" id="original_language" name="original_language" class="form-control" maxlength="10" placeholder="e.g. en, ne, hi" required />
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <input type="text" id="status" name="status" class="form-control" maxlength="50" placeholder="e.g. Released" required />
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="release_date" class="form-label">Release Date</label>
                    <input type="date" id="release_date" name="release_date" class="form-control" required />
                </div>

                <div class="form-group">
                    <label for="runtime" class="form-label">Runtime (Minutes)</label>
                    <input type="number" id="runtime" name="runtime" class="form-control" min="1" placeholder="e.g. 148" required />
                </div>
            </div>

            <div class="form-group">
                <label for="tagline" class="form-label">Tagline</label>
                <input type="text" id="tagline" name="tagline" class="form-control" maxlength="255" placeholder="Short catchy phrase" />
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="genres" class="form-label">Genres (Comma separated)</label>
                    <input type="text" id="genres" name="genres" class="form-control" placeholder="Action, Adventure, Sci-Fi" required />
                </div>

                <div class="form-group">
                    <label for="keywords" class="form-label">Keywords (Comma separated)</label>
                    <input type="text" id="keywords" name="keywords" class="form-control" placeholder="dream, heist, spy" required />
                </div>
            </div>

            <div class="form-group">
                <label for="overview" class="form-label">Synopsis / Overview</label>
                <textarea id="overview" name="overview" class="form-control" rows="4" placeholder="Brief movie description..." required></textarea>
            </div>

            <div class="form-group">
                <label for="poster_path" class="form-label">Poster Image</label>
                <div class="file-input-wrapper">
                    <input type="file" id="poster_path" name="poster_path" class="form-control" accept=".jpg, .jpeg, .png" required />
                </div>
            </div>

            <div class="form-actions">
                <a href="admin.php" class="back-link">&larr; Back to Dashboard</a>
                <input type="submit" name="submit" value="Add Movie to Catalog" class="btn-submit" />
            </div>
        </form>
    </div>
</div>

</body>
</html>

