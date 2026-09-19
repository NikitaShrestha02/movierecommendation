<?php
    
    if(!isset($_COOKIE['uemail']))
    {
        header('location: nlogin.php');
        die();
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Movie Recommendation System</title>
    <style>
       body, html {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    height: 100%;
    width: 100%;
}

.slideshow {
    position: relative;
    overflow: hidden;
    width: 100%; 
    height: 440px; 
    background-color: #0f141c;
}

.slides {
    display: flex;
    width: 100%;
    height: 100%;
}

.slide {
    position: relative;
    flex: 0 0 100%;
    width: 100%;
    height: 100%;
}

.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 25%;
    display: block;
}

.synopsis {
    position: absolute;
    bottom: 32px;
    left: 40px;
    max-width: 540px;
    padding: 18px 22px;
    background: rgba(15, 20, 28, 0.88);
    border: 1px solid #263245;
    border-radius: 8px;
    z-index: 2; 
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.synopsis h1 {
    color: #ffffff;
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 8px 0;
    letter-spacing: -0.01em;
}

.synopsis p {
    color: #cbd5e1;
    font-size: 13.5px;
    line-height: 1.5;
    margin: 0;
}

.slide::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(15, 20, 28, 0.8) 0%, rgba(15, 20, 28, 0.2) 60%, transparent 100%);
    z-index: 1; 
} 
    </style>
</head>
<body>
 


<!-- Slideshow -->
<div class="slideshow">
  <div class="slides">
    <?php
    include("connection.php");

    // Fetch first 5 movies based on ID
    $query = "SELECT * FROM movies WHERE poster_path IS NOT NULL AND overview IS NOT NULL ORDER BY id ASC LIMIT 5";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
       $posterPath = 'uploads/' . basename($row['poster_path']);
        echo '<div class="slide">';
        echo '<img src="' . $posterPath . '" alt="' . htmlspecialchars($row['original_title']) . '">';
        echo '<div class="synopsis">';
        echo '<h1>' . htmlspecialchars($row['original_title']) . '</h1>';
        echo '<p>' . htmlspecialchars(mb_strimwidth($row['overview'], 0, 150, '...')) . '</p>';
        echo '</div>';
        echo '</div>';
    }
    ?>
  </div>
</div>

        

<?php
include("moviesinsertion.php");
?>

<script>
    let slideIndex = 0;
    const slides = document.getElementsByClassName("slide");

    function showSlides() {
        if (slides.length === 0) return;
        for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slideIndex++;
        if (slideIndex > slides.length) {
            slideIndex = 1;
        }
        slides[slideIndex - 1].style.display = "block";
        setTimeout(showSlides, 5000);
    }

    showSlides(); 
</script>

    

</body>
</html>