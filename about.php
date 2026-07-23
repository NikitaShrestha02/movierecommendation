<?php
session_start();
if(!isset($_COOKIE['uemail'])) {
    header('location: nlogin.php');
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Recommendation System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0D0E30; 
            
        }

        .container {
            max-width: 800px; 
            margin: auto; 
            padding: 20px; 
            background-color: #0b0b3e; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); 
           
        }

        section#about {
            margin-top: 20px; 
        }

        section#about h2 {
            color: white; 
            font-size: 24px;
            margin-bottom: 10px;
        }

        section#about p {
            color: white; 
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        section#about ul {
            color: white; 
            font-size: 16px;
            line-height: 1.5;
            padding-left: 20px; 
        }

        section#about ul li {
            margin-bottom: 5px; 
        }
        body, html {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
    </style>
<?php include("navigation.php");?>
</head>
<body>
  <div class="container">
    <section id="about">
        <h2>About Us</h2>
      <section>
  <h2>Personalized Movie Recommendations Just For You</h2>
  <p>Discover movies tailored to your unique taste! Our smart recommendation engine learns what you love and suggests titles you’re sure to enjoy from hidden gems to blockbuster hits.</p>
  
  <ul>
    <li><strong>Curated Picks:</strong> Movies handpicked based on your search history and preferences.</li>
    <li><strong>Explore New Genres:</strong> Expand your horizons with recommendations from genres you prefer.</li>
    <li><strong>Always Fresh:</strong> New suggestions added regularly to keep your watchlist exciting.</li>
  </ul>
  
  <p>Start exploring now and find your next favorite movie with ease!</p>
</section>


 
</body>
</html>
