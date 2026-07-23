<?php
include('connection.php');

if (isset($_POST['submit'])) {
    $id = $_POST['id'];  
    $original_language = $_POST['original_language'];
    $original_title = $_POST['original_title'];
    $overview = $_POST['overview'];
    $release_date = $_POST['release_date'];
    $runtime = $_POST['runtime'];
    $status = $_POST['status'];
    $tagline = $_POST['tagline'];
    $genres = $_POST['genres'];
    $keywords = $_POST['keywords'];

    // Check if ID already exists
    $id_check_query = "SELECT id FROM movies WHERE id = ?";
    $id_check_stmt = mysqli_prepare($conn, $id_check_query);
    mysqli_stmt_bind_param($id_check_stmt, "i", $id);
    mysqli_stmt_execute($id_check_stmt);
    mysqli_stmt_store_result($id_check_stmt);

    if (mysqli_stmt_num_rows($id_check_stmt) > 0) {
        echo "<script>alert('Movie ID already exists. Please use a different ID.'); window.history.back();</script>";
        mysqli_stmt_close($id_check_stmt);
        exit;
    }
    mysqli_stmt_close($id_check_stmt);

    // Check if title already exists
    $title_check_query = "SELECT id FROM movies WHERE original_title = ?";
    $title_check_stmt = mysqli_prepare($conn, $title_check_query);
    mysqli_stmt_bind_param($title_check_stmt, "s", $original_title);
    mysqli_stmt_execute($title_check_stmt);
    mysqli_stmt_store_result($title_check_stmt);

    if (mysqli_stmt_num_rows($title_check_stmt) > 0) {
        echo "<script>alert('Movie title already exists. Please use a different title.'); window.history.back();</script>";
        mysqli_stmt_close($title_check_stmt);
        exit;
    }
    mysqli_stmt_close($title_check_stmt);

    // Handle poster upload
    if (isset($_FILES['poster_path']) && $_FILES['poster_path']['error'] == 0) {
        $file = $_FILES['poster_path'];
        $file_name = time() . '_' . basename($file['name']);
        $file_tmp = $file['tmp_name'];
        $upload_directory = "uploads/";
        $upload_path = $upload_directory . $file_name;

        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Insert into database
            $insert_query = "INSERT INTO movies (id, original_language, original_title, overview, release_date, runtime, status, tagline, genres, keywords, poster_path)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "issssisssss", $id, $original_language, $original_title, $overview, $release_date, $runtime, $status, $tagline, $genres, $keywords, $upload_path);

            if (mysqli_stmt_execute($insert_stmt)) {
                mysqli_stmt_close($insert_stmt);
                header('Location: admin.php');
                exit();
            } else {
                echo "<script>alert('Error inserting data: " . mysqli_error($conn) . "');</script>";
                mysqli_stmt_close($insert_stmt);
            }
        } else {
            echo "<script>alert('Failed to upload poster image.');</script>";
        }
    } else {
        echo "<script>alert('No poster file uploaded or upload error occurred.');</script>";
    }
} else {
    echo "<script>alert('Form was not submitted.');</script>";
    exit;
}
?>
