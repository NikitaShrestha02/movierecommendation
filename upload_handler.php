<?php
$host = 'localhost';
$db = 'movie_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if (isset($_FILES['poster']) && isset($_POST['movie_id'])) {
        $movieId = intval($_POST['movie_id']);
        $file = $_FILES['poster'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = basename($file['name']);
            $targetDir = 'uploads/';
            $targetFile = $targetDir . uniqid() . '_' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                // Save the image path in the database
                $stmt = $pdo->prepare("UPDATE movies SET poster_path = ? WHERE id = ?");
                $stmt->execute([$targetFile, $movieId]);

                echo "Upload successful and path saved!";
            } else {
                echo "Failed to move uploaded file.";
            }
        } else {
            echo "Upload error.";
        }
    } else {
        echo "Invalid submission.";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
