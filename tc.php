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
    <title>Terms &amp; Conditions - Movie Recommendation</title>
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

        .terms-content {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .term-section {
            border-bottom: 1px solid #242e40;
            padding-bottom: 20px;
        }

        .term-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .term-heading {
            color: #f8fafc;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .term-number {
            color: #60a5fa;
            font-weight: 700;
        }

        .term-body {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.65;
            margin: 0;
        }

        .term-body a {
            color: #60a5fa;
            text-decoration: none;
        }

        .term-body a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <div class="header-card">
        <span class="badge">Legal &amp; Policies</span>
        <h1 class="page-title">Terms and Conditions</h1>
        <p class="page-desc">Please review these terms governing your use of our movie recommendation service and personal watchlist features.</p>
    </div>

    <div class="terms-content">
        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">1.</span> Use of the Service</h3>
            <p class="term-body">You agree to use our platform solely for personal and lawful purposes, adhering to community standards and applicable regulations without infringing upon others' enjoyment.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">2.</span> Account Registration &amp; Security</h3>
            <p class="term-body">To save watched films and receive calibrated recommendations, you must maintain an active account with accurate information and keep your credentials confidential.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">3.</span> Recommendation Methodology</h3>
            <p class="term-body">Our platform uses statistical similarity calculations (including K-Nearest Neighbors vector analysis) to provide suggestions. These recommendations serve as curated discovery guidance and do not guarantee personal subjective satisfaction.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">4.</span> Intellectual Property</h3>
            <p class="term-body">All platform software, user interface styling, logos, and catalog presentations are protected by copyright and intellectual property laws. Unauthorized reproduction or scraping is prohibited.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">5.</span> Limitation of Liability</h3>
            <p class="term-body">The service is provided on an "as is" basis. We are not liable for incidental service interruptions, catalog inaccuracies, or third-party metadata discrepancies.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">6.</span> Privacy &amp; Data Handling</h3>
            <p class="term-body">Your ratings and genre selections are stored securely to calculate recommendations. We do not sell or disclose your personal data to external advertisers.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">7.</span> Policy Updates</h3>
            <p class="term-body">We reserve the right to periodically revise these terms to reflect new algorithmic features or legal guidelines. Continued use signifies agreement to modified terms.</p>
        </div>

        <div class="term-section">
            <h3 class="term-heading"><span class="term-number">8.</span> Inquiries &amp; Support</h3>
            <p class="term-body">If you have any questions regarding these terms, please feel free to <a href="contact.php">contact our support team</a>.</p>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

</body>
</html>

