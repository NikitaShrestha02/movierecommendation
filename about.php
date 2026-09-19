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
    <title>About Us - Movie Recommendation</title>
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
            max-width: 980px;
            width: 100%;
            margin: 36px auto;
            padding: 0 20px;
            flex: 1;
        }

        .about-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 36px 32px;
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
            margin-bottom: 14px;
        }

        .about-hero-title {
            color: #f8fafc;
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 14px 0;
            letter-spacing: -0.01em;
            line-height: 1.3;
        }

        .about-hero-desc {
            color: #94a3b8;
            font-size: 15.5px;
            line-height: 1.65;
            margin: 0 0 24px 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-top: 24px;
        }

        .feature-card {
            background-color: #131924;
            border: 1px solid #232d3f;
            border-radius: 6px;
            padding: 20px;
            transition: border-color 0.2s ease, transform 0.15s ease;
        }

        .feature-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .feature-icon {
            font-size: 22px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .feature-title {
            color: #f1f5f9;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .feature-text {
            color: #94a3b8;
            font-size: 13.5px;
            line-height: 1.55;
            margin: 0;
        }

        .workflow-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 32px;
        }

        .section-header {
            margin-bottom: 22px;
        }

        .section-title {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }

        .section-subtitle {
            color: #94a3b8;
            font-size: 14px;
            margin: 0;
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 18px;
        }

        .step-item {
            background-color: #131924;
            border: 1px solid #232d3f;
            border-radius: 6px;
            padding: 20px 18px;
            position: relative;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            border-radius: 50%;
            margin-bottom: 12px;
        }

        .step-heading {
            color: #f1f5f9;
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 6px 0;
        }

        .step-description {
            color: #94a3b8;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }

        .cta-box {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid #242e40;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .cta-text {
            color: #cbd5e1;
            font-size: 14.5px;
            margin: 0;
        }

        .cta-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 5px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #1e2637;
            color: #cbd5e1;
            border: 1px solid #2d384c;
        }

        .btn-secondary:hover {
            background-color: #283449;
            color: #ffffff;
        }

        @media (max-width: 640px) {
            .about-card, .workflow-card {
                padding: 24px 18px;
            }
            .about-hero-title {
                font-size: 22px;
            }
            .cta-box {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <div class="about-card">
        <span class="badge">About the Platform</span>
        <h1 class="about-hero-title">Personalized Movie Recommendations Just For You</h1>
        <p class="about-hero-desc">
            Discover movies tailored to your unique taste! Our smart recommendation engine learns what you love and suggests titles you’re sure to enjoy — from hidden indie gems to blockbuster hits.
        </p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Curated Picks</h3>
                <p class="feature-text">Movies handpicked based on your search history, watched library, and custom rating preferences.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🧭</div>
                <h3 class="feature-title">Explore New Genres</h3>
                <p class="feature-text">Expand your horizons with intelligent suggestions from adjacent genres matched to your taste profile.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">KNN Matching Engine</h3>
                <p class="feature-text">Calculates multidimensional similarity vectors to rank the closest matches to films you love.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3 class="feature-title">Interactive Ratings</h3>
                <p class="feature-text">Rate movies from 1 to 5 stars to dynamically adjust your preference centroid and get even sharper recommendations.</p>
            </div>
        </div>
    </div>

    <div class="workflow-card">
        <div class="section-header">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">A straightforward 3-step recommendation loop designed around your viewing habits.</p>
        </div>

        <div class="steps-container">
            <div class="step-item">
                <div class="step-number">1</div>
                <h4 class="step-heading">Browse & Watch</h4>
                <p class="step-description">Explore our catalog and add films you have watched to your personal collection.</p>
            </div>

            <div class="step-item">
                <div class="step-number">2</div>
                <h4 class="step-heading">Rate Your Favorites</h4>
                <p class="step-description">Give your honest 1 to 5-star rating on any film's details page to calibrate your taste profile.</p>
            </div>

            <div class="step-item">
                <div class="step-number">3</div>
                <h4 class="step-heading">Receive Tailored Picks</h4>
                <p class="step-description">The KNN algorithm computes distance vectors to deliver accurate, freshly ranked movie suggestions.</p>
            </div>
        </div>

        <div class="cta-box">
            <p class="cta-text">Ready to find your next favorite film?</p>
            <div class="cta-actions">
                <a href="posters.php" class="btn btn-secondary">Browse All Movies</a>
                <a href="index.php" class="btn btn-primary">Go to Recommendations</a>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

</body>
</html>

