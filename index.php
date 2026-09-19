<?php
session_start();

if(!isset($_COOKIE['uemail']))
{
    header("Location: nlogin.php");
    exit();
}

if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "movie_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Recommendation</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c; 
            margin: 0;
            padding: 0;
            color: #cbd5e1;
        }

        .recommendations {
            padding: 32px 24px;
            max-width: 1200px;
            margin: 32px auto;
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }

        .recommendations h1 {
            text-align: left;
            font-size: 22px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 24px;
            color: #f8fafc;
            letter-spacing: -0.01em;
        }

        .movie-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
            justify-items: center;
        }

        .no-recommendations {
            text-align: center;
            color: #94a3b8;
            font-size: 15px;
            padding: 24px 0;
        }

        .movie-poster {
            width: 100%;
            max-width: 190px;
            height: 275px;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            background: #131924;
            border: 1px solid #263245;
        }

        .movie-poster:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.45);
            border-color: #3b82f6;
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 7px;
            display: block;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(15, 20, 28, 0.95) 0%, rgba(15, 20, 28, 0.75) 55%, rgba(15, 20, 28, 0.3) 100%);
            opacity: 0;
            transition: opacity 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            padding: 16px 12px;
            text-align: center;
        }

        .movie-poster:hover .overlay {
            opacity: 1;
        }

        .movie-title {
            font-size: 14px;
            color: #ffffff;
            text-align: center;
            margin-bottom: 12px;
            font-weight: 600;
            line-height: 1.3;
        }

        .show-details {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 7px 16px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease;
            display: inline-block;
        }

        .show-details:hover {
            background-color: #1d4ed8;
            color: #ffffff;
        }
    </style>
</head>
<body>

<?php
include("navigation.php");
include("slideshow.php");

echo '<div id="main-recommendations-container">';
include("fetch_recommendations.php");
echo '</div>';

if (isset($conn) && $conn instanceof mysqli) {
    try {
        @$conn->close();
    } catch (Throwable $e) {
        // Connection already closed
    }
}
include("footer.php");
?>

</body>
</html>
