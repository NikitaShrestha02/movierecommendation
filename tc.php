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
   <section id="faqs">
  <h2>Terms and Conditions</h2>

  <p>Welcome to our movie recommendation platform. By using our services, you agree to the following terms and conditions. Please read them carefully.</p>

  <h3>1. Use of the Service</h3>
  <p>You agree to use our platform only for lawful purposes and in a way that does not infringe the rights of others or restrict their use and enjoyment of the service.</p>

  <h3>2. Account Registration</h3>
  <p>To access personalized recommendations features, you must create an account and provide accurate, complete information. You are responsible for maintaining the confidentiality of your account credentials.</p>

  <h3>3. Movie Recommendations</h3>
  <p>Our recommendation system provides suggestions based on your preferences and search history. Recommendations are for guidance only, and we do not guarantee satisfaction with all suggested movies.</p>

  <h3>4. Intellectual Property</h3>
  <p>All content on this platform, including logos, text, images, and software, is owned by or licensed to us and is protected by intellectual property laws. You may not use any content without prior written permission.</p>

  <h3>5. Limitation of Liability</h3>
  <p>We are not liable for any direct or indirect damages resulting from the use or inability to use our platform, including errors in recommendations, search error, or service interruptions.</p>

  <h3>6. Privacy</h3>
  <p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect, use, and protect your information.</p>

  <h3>7. Changes to Terms</h3>
  <p>We reserve the right to update these terms at any time. Continued use of the platform after changes implies acceptance of the new terms.</p>

  <h3>8. Contact Us</h3>
  <p>If you have any questions about these terms, please contact our support team.</p>
</section>


</body>
</html>
