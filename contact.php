<?php
session_start();
if (!isset($_COOKIE['uemail'])) {
    header('location: nlogin.php');
    die();
}
if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}

$userEmail = $_SESSION['uemail'] ?? '';
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? $userEmail);
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $errorMessage = "Please enter your message before sending.";
    } else {
        $successMessage = "Thank you" . (!empty($name) ? ", " . htmlspecialchars($name) : "") . "! Your message has been sent successfully. We will get back to you at " . htmlspecialchars($email) . " shortly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Movie Recommendation</title>
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
            max-width: 1000px;
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

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 28px;
            align-items: start;
        }

        .info-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: border-color 0.2s ease, transform 0.15s ease;
        }

        .info-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .info-icon {
            font-size: 20px;
            width: 38px;
            height: 38px;
            background-color: #131924;
            border: 1px solid #263347;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-card-title {
            color: #f1f5f9;
            font-size: 15px;
            font-weight: 600;
            margin: 0;
        }

        .info-card-value {
            color: #cbd5e1;
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .info-card-value a {
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .info-card-value a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        .info-card-note {
            color: #64748b;
            font-size: 12.5px;
            margin: 0;
        }

        .form-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 28px 26px;
        }

        .form-title {
            color: #f8fafc;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 18px 0;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13.5px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control {
            width: 100%;
            background-color: #131924;
            border: 1px solid #2d384c;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 110px;
        }

        .btn-submit {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 10px 22px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            font-family: inherit;
            display: inline-block;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }

        .quick-help-card {
            background-color: #131924;
            border: 1px solid #242e40;
            border-radius: 6px;
            padding: 16px;
            margin-top: 6px;
        }

        .quick-help-card p {
            margin: 0;
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .quick-help-card a {
            color: #60a5fa;
            text-decoration: none;
        }

        .quick-help-card a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            .header-card, .form-card {
                padding: 22px 18px;
            }
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <div class="header-card">
        <span class="badge">Support & Assistance</span>
        <h1 class="page-title">Contact Us</h1>
        <p class="page-desc">If you have any queries, feedback, or need assistance with movie recommendations, feel free to get in touch with us.</p>
    </div>

    <div class="contact-grid">
        <!-- Contact Information Column -->
        <div class="info-column">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">📞</div>
                    <h3 class="info-card-title">Phone Numbers</h3>
                </div>
                <p class="info-card-value">01-456789 &nbsp;|&nbsp; 01-424689</p>
                <p class="info-card-note">Sunday – Friday: 9:00 AM – 6:00 PM</p>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">✉️</div>
                    <h3 class="info-card-title">Email Address</h3>
                </div>
                <p class="info-card-value"><a href="mailto:SNscreen@gmail.com">SNscreen@gmail.com</a></p>
                <p class="info-card-note">We typically respond within 24 hours</p>
            </div>

            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-icon">📍</div>
                    <h3 class="info-card-title">Office Location</h3>
                </div>
                <p class="info-card-value">Kathmandu, Nepal</p>
                <p class="info-card-note">Movie Recommendation System Team</p>
            </div>

            <div class="quick-help-card">
                <p>Looking for quick answers? Browse our <a href="faqs.php">Frequently Asked Questions</a> or read our <a href="tc.php">Terms &amp; Conditions</a>.</p>
            </div>
        </div>

        <!-- Interactive Contact Form Column -->
        <div class="form-card">
            <h2 class="form-title">Send Us a Message</h2>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>

            <form action="contact.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($userEmail); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">Subject</label>
                    <select id="subject" name="subject" class="form-control">
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Recommendation Feedback">Recommendation Feedback</option>
                        <option value="Bug Report">Bug Report</option>
                        <option value="Movie Request">Movie Request</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" placeholder="Write your query or feedback here..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

</body>
</html>

