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
    <title>Movie Recommendation Systems</title>
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

        section#faqs {
            margin-top: 20px; 
        }

        section#faqs h2 {
            color: white; 
            font-size: 24px;
            margin-bottom: 10px;
            text-align: center;
        }
         section#faqs h3 {
            color: white; 
            font-size: 24px;
            margin-bottom: 10px;
        }

        section#faqs p {
            color: white; 
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        section#faqs ul {
            color: white; 
            font-size: 16px;
            line-height: 1.5;
            padding-left: 20px; 
        }

        section#faqs ul li {
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
    <section id ="faqs">
  <h2>Frequently Asked Questions (FAQs)</h2>
  
  <h3>How does the movie recommendation system work?</h3>
  <p>Our system analyzes your search history, selected genres to suggest movies that match your preferences. The more you interact, the better your recommendations become!</p>
  
  <h3>Can I customize my movie preferences?</h3>
  <p>Yes! You can update your favorite genres and add movies in already watched section to help us fine-tune your recommendations.</p>
  
  <h3>Are the recommendations updated regularly?</h3>
  <p>Absolutely. Our platform constantly refreshes recommendations to include the latest releases and trending movies, so you always have fresh options to explore.</p>
  
  <h3>Do I need to create an account to get personalized recommendations?</h3>
  <p>Yes, creating an account allows us to save your preferences and search history to provide you with tailored movie suggestions.</p>
  
  <h3>Is there a fee for using the recommendation feature?</h3>
  <p>No, personalized recommendations are completely free as part of our movie recommendation service.</p>
  
  <h3>What if I don’t like the recommended movies?</h3>
  <p>No worries! You can always explore movies by genre, keywords or new releases to find something you enjoy.</p>
  
</section>

</body>
</html>
