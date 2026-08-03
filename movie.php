<?php
// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "movie_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get movie ID from URL
$movie_id = $_GET['id'] ?? null;
if (!$movie_id || !is_numeric($movie_id)) {
    echo "<h2>Invalid movie ID.</h2>";
    exit;
}

// Fetch movie from DB
$stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

$movie = $result->fetch_assoc();

$conn->close();

if (!$movie) {
    echo "<h2>Movie not found.</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?php echo htmlspecialchars($movie['original_title']); ?> - Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0D0E30;
            margin: 0;
            padding: 0 20px;
            color: #ddd;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #1a1c4d;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.6);
            padding: 30px 40px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            color: #eee;
        }
        .poster {
            flex: 1 1 300px;
            max-width: 300px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.8);
        }
        .poster img {
            width: 100%;
            display: block;
            border-radius: 10px;
        }
        .details {
            flex: 2 1 500px;
        }
        h1 {
            margin-top: 0;
            font-size: 2.5rem;
            color: #82aaff;
            margin-bottom: 10px;
        }
        .description {
            line-height: 1.6;
            font-size: 1.1rem;
            margin-bottom: 25px;
            color: #ccc;
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-list li {
            margin-bottom: 12px;
            font-size: 1.1rem;
            color: #ccc;
        }
        .info-list li strong {
            color: #82aaff;
        }
        a.back-link {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            color: #82aaff;
            font-weight: 600;
            border: 2px solid #82aaff;
            padding: 8px 18px;
            border-radius: 6px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        a.back-link:hover {
            background-color: #82aaff;
            color: #0D0E30;
        }
        @media (max-width: 700px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }
            .poster, .details {
                max-width: 100%;
                flex: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="poster">
        <?php
        $poster = !empty($movie['poster_path']) ? $movie['poster_path'] : 'default.jpg';
        echo '<img src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($movie['original_title']) . ' Poster">';
        ?>
    </div>
    <div class="details">
        <h1><?php echo htmlspecialchars($movie['original_title']); ?></h1>

        <div class="description">
            <?php echo nl2br(htmlspecialchars($movie['overview'] ?? 'No description available.')); ?>
        </div>

        <ul class="info-list">
            <li><strong>Genres:</strong> <?php echo htmlspecialchars($movie['genres']); ?></li>
            <li><strong>Release Date:</strong> <?php echo htmlspecialchars($movie['release_date']); ?></li>
            <li><strong>Keywords:</strong> <?php echo htmlspecialchars($movie['keywords']); ?></li>
        </ul>

        <a href="index.php" class="back-link">← Back to Gallery</a>
    </div>
</div>

</body>
</html>
