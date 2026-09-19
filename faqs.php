<?php
session_start();
if (!isset($_COOKIE['uemail'])) {
    header('location: nlogin.php');
    die();
}
if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequently Asked Questions - Movie Recommendation</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c;
            color: #cbd5e1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .page-container {
            max-width: 960px;
            width: 100%;
            margin: 36px auto;
            padding: 0 20px;
            flex: 1;
        }

        .header-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 32px;
            margin-bottom: 28px;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #60a5fa;
            background-color: rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.25);
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .page-title {
            color: #f8fafc;
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 10px 0;
            letter-spacing: -0.01em;
        }

        .page-desc {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .faq-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: border-color 0.2s ease, transform 0.15s ease;
        }

        .faq-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .faq-question {
            color: #f8fafc;
            font-size: 16.5px;
            font-weight: 600;
            margin: 0 0 10px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .faq-icon {
            color: #60a5fa;
            font-size: 17px;
            line-height: 1.3;
        }

        .faq-answer {
            color: #94a3b8;
            font-size: 14.5px;
            line-height: 1.6;
            margin: 0;
            padding-left: 27px;
        }

        .help-banner {
            margin-top: 32px;
            background-color: #131924;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .help-text h4 {
            color: #f1f5f9;
            font-size: 16px;
            margin: 0 0 4px 0;
        }

        .help-text p {
            color: #94a3b8;
            font-size: 13.5px;
            margin: 0;
        }

        .btn-contact {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 9px 20px;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .btn-contact:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <div class="header-card">
        <span class="badge">Help &amp; Answers</span>
        <h1 class="page-title">Frequently Asked Questions</h1>
        <p class="page-desc">Common questions about personalized recommendations, rating calculations, and managing your account.</p>
    </div>

    <div class="faq-list">
        <div class="faq-card">
            <h3 class="faq-question"><span class="faq-icon">Q:</span> How does the movie recommendation system work?</h3>
            <p class="faq-answer">Our system analyzes your search history, selected genre preferences, and watched films. When you rate movies, it computes multidimensional similarity vectors to rank titles that most closely match your taste.</p>
        </div>

        <div class="faq-card">
            <h3 class="faq-question"><span class="faq-icon">Q:</span> Can I customize my movie preferences?</h3>
            <p class="faq-answer">Yes! You can update your favorite genres in your profile settings and add any film to your Watched Movies catalog with a custom star rating to recalibrate your recommendations.</p>
        </div>

        <div class="faq-card">
            <h3 class="faq-question"><span class="faq-icon">Q:</span> Are the recommendations updated regularly?</h3>
            <p class="faq-answer">Absolutely. The recommendation engine dynamically adapts whenever you rate a film or add new titles to your watched list, ensuring fresh suggestions every time you visit.</p>
        </div>

        <div class="faq-card">
            <h3 class="faq-question"><span class="faq-icon">Q:</span> Do I need an account to get recommendations?</h3>
            <p class="faq-answer">Yes, creating a free account allows the platform to securely store your watched titles, personal star ratings, and personalized preference centroids.</p>
        </div>

        <div class="faq-card">
            <h3 class="faq-question"><span class="faq-icon">Q:</span> Is there any fee for using this platform?</h3>
            <p class="faq-answer">No, personalized movie recommendations and all discovery features are 100% free.</p>
        </div>

        <div class="faq-card">
            <h3 class="faq-question"><span class="faq-icon">Q:</span> What if I want to explore movies outside my usual taste?</h3>
            <p class="faq-answer">You can browse our complete movie catalog by genre, keywords, or release dates from the Movies page at any time.</p>
        </div>
    </div>

    <div class="help-banner">
        <div class="help-text">
            <h4>Still have questions or need assistance?</h4>
            <p>Our support team is happy to help you with any inquiries or technical issues.</p>
        </div>
        <a href="contact.php" class="btn-contact">Contact Support</a>
    </div>
</div>

<?php include("footer.php"); ?>

</body>
</html>

