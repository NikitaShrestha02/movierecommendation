<?php
include('connection.php');

// DELETE movie
if (isset($_GET['delete'])) {
    $remove_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM `watched_movies` WHERE movies_id='$remove_id'") or die('Failed to delete related watched movies');
    mysqli_query($conn, "DELETE FROM `movies` WHERE id='$remove_id'") or die('Failed to delete movie');
    header('Location: nowshowingform.php');
    exit;
}

// UPDATE movie
if (isset($_POST['update_movie'])) {
    $update_id = $_POST['movie_id'];
    $update_title = $_POST['original_title'];
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
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Handle search
$search_term = "";
if (isset($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $query = "SELECT * FROM movies WHERE original_title LIKE '%$search_term%' LIMIT 10";
} else {
    $query = "SELECT * FROM movies LIMIT 10";
}
$select_movies = mysqli_query($conn, $query) or die('Query failed');
?>
<html>
<head>
    <link href="css/shownow.css" rel="stylesheet">
    <style>
        form.search-form {
            margin-bottom: 20px;
        }
        input[type="text"], input[type="file"] {
            margin-bottom: 5px;
        }
        img {
            max-height: 100px;
        }
    </style>
</head>
<body>
    <?php
    include("adnav.php"); 
    ?>
<section id="movies">
    <h2>MANAGE MOVIES</h2>
    <form method="get" action="" class="search-form">
        <input type="text" name="search" placeholder="Search by title..." value="<?php echo htmlspecialchars($search_term); ?>">
        <input type="submit" value="Search">
    </form>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Original Title</th>
                <th>Poster</th>
                <th></th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (mysqli_num_rows($select_movies) > 0) {
            while ($movie = mysqli_fetch_assoc($select_movies)) {
        ?>
            <tr>
                <td><?php echo $movie['id']; ?></td>
                <td><?php echo htmlspecialchars($movie['original_title']); ?></td>
                <td><img src="<?php echo htmlspecialchars($movie['poster_path']); ?>" alt="poster" width="80"></td>
                <td>
                    <a href="shownow.php?delete=<?php echo $movie['id']; ?>" class="delete-btn"
                       onclick="return confirm('Are you sure you want to delete this movie?');">Delete</a>
                </td>
                <td>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                        <input type="hidden" name="current_poster_path" value="<?php echo $movie['poster_path']; ?>">
                        <input type="text" name="original_title" value="<?php echo htmlspecialchars($movie['original_title']); ?>" required>
                        <input type="file" name="poster_path" accept=".jpg, .jpeg, .png">
                        <input type="submit" name="update_movie" value="Update">
                    </form>
                </td>
            </tr>
        <?php
            }
        } else {
            echo "<tr><td colspan='5'>No movies found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</section>
</body>
</html>
