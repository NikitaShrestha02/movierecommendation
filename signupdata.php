<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $Password = $_POST['Password'];
    $genres = isset($_POST['genres']) ? implode(',', $_POST['genres']) : '';

    $sql = "INSERT INTO user(name, email, address, contact, Password, preferred_genres) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $email, $address, $contact, $Password, $genres);

    if ($stmt->execute()) {
        header("location: nlogin.php");
        exit;
    } else {
        echo "Your account cannot be created. Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>