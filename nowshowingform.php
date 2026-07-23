<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Movie Details</title>
    <link href="css/nowshowingform.css" rel="stylesheet" />
</head>
<body>
    <?php include("adnav.php"); ?>

    <div class="content-section">
        <h1>ADD MOVIE DETAILS</h1>
        <form action="add_movie.php" method="post" autocomplete="off" enctype="multipart/form-data">
            <label for="id">Movie ID:</label>
            <input type="number" name="id" id="id" required>

            <label for="original_language">Original Language:</label>
            <input type="text" id="original_language" name="original_language" maxlength="10" required />

            <label for="original_title">Original Title:</label>
            <input type="text" id="original_title" name="original_title" maxlength="255" required />

            <label for="overview">Overview:</label>
            <textarea id="overview" name="overview" rows="4" required></textarea>

            <label for="release_date">Release Date:</label>
            <input type="date" id="release_date" name="release_date" required />

            <label for="runtime">Runtime (minutes):</label>
            <input type="number" id="runtime" name="runtime" min="1" required />

            <label for="status">Status:</label>
            <input type="text" id="status" name="status" maxlength="50" required />

            <label for="tagline">Tagline:</label>
            <input type="text" id="tagline" name="tagline" maxlength="255" />

            <label for="genres">Genres (comma separated):</label>
            <textarea id="genres" name="genres" rows="2" required></textarea>

            <label for="keywords">Keywords (comma separated):</label>
            <textarea id="keywords" name="keywords" rows="2" required></textarea>

            <label for="poster_path">Poster Image:</label>
            <input type="file" id="poster_path" name="poster_path" accept=".jpg, .jpeg, .png" required />

            <input type="submit" name="submit" value="Submit" />
        </form>
    </div>
</body>
</html>
