<?php
session_start();

$search = $_GET['search'] ?? '';

if (strlen($search) >= 2) {
    $_SESSION['last_search_keyword'] = $search;
}

$host = "localhost";
$user = "root"; // your DB username
$pass = "";     // your DB password
$db   = "movie_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = $_GET['search'] ?? '';

if (strlen($search) < 2) {
    exit;
}

$sql = "SELECT id, original_title FROM movies WHERE keywords LIKE ? OR original_title LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$param = "%" . $search . "%";
$stmt->bind_param("ss", $param, $param);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='result-item' onclick=\"window.location.href='details.php?id={$row['id']}'\">" . htmlspecialchars($row['original_title']) . "</div>";
    }
} else {
    echo "<div class='result-item'>No movies found for that keyword.</div>";
}

$conn->close();
?>