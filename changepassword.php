<?php
session_start();
if (!isset($_COOKIE['uemail']) && !isset($_SESSION['uemail'])) {
    header('location: nlogin.php');
    exit();
}

if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}

include('connection.php');

$email = $_SESSION['uemail'];
$passSuccess = '';
$passError = '';

$res = mysqli_query($conn, "SELECT Password FROM user WHERE email='$email'");
$row = mysqli_fetch_assoc($res);
$currentDbPassword = $row['Password'] ?? '';

if (isset($_POST['submit'])) {
    $cur_pass = $_POST['cur_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $con_pass = $_POST['con_pass'] ?? '';

    if ($cur_pass !== $currentDbPassword) {
        $passError = "The current password you entered is incorrect.";
    } elseif (empty($new_pass)) {
        $passError = "Please enter a valid new password.";
    } elseif ($new_pass !== $con_pass) {
        $passError = "New password and confirmation password do not match.";
    } else {
        $update_user = $conn->prepare("UPDATE `user` SET Password=? WHERE email=?");
        $update_user->bind_param("ss", $new_pass, $email);
        if ($update_user->execute()) {
            $passSuccess = "Password updated successfully!";
            $currentDbPassword = $new_pass;
        } else {
            $passError = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Movie Recommendation</title>
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
            max-width: 580px;
            width: 100%;
            margin: 44px auto;
            padding: 0 20px;
            flex: 1;
        }

        .card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 32px;
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

        .card-title {
            color: #f8fafc;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: -0.01em;
        }

        .card-subtitle {
            color: #94a3b8;
            font-size: 14px;
            margin: 0 0 24px 0;
            line-height: 1.5;
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

        .btn-submit {
            width: 100%;
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-family: inherit;
            margin-top: 6px;
        }

        .btn-submit:hover {
            background-color: #1d4ed8;
        }

        .card-footer-links {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #242e40;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .card-footer-links a {
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .card-footer-links a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php include("navigation.php"); ?>

<div class="page-container">
    <div class="card">
        <span class="badge">Security Settings</span>
        <h1 class="card-title">Change Password</h1>
        <p class="card-subtitle">Enter your current password followed by your desired new password.</p>

        <?php if (!empty($passSuccess)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($passSuccess); ?></div>
        <?php endif; ?>

        <?php if (!empty($passError)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($passError); ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label class="form-label" for="cur_pass">Current Password</label>
                <input type="password" id="cur_pass" name="cur_pass" class="form-control" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="new_pass">New Password</label>
                <input type="password" id="new_pass" name="new_pass" class="form-control" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="con_pass">Confirm New Password</label>
                <input type="password" id="con_pass" name="con_pass" class="form-control" required autocomplete="new-password">
            </div>

            <button type="submit" name="submit" class="btn-submit">Update Password</button>
        </form>

        <div class="card-footer-links">
            <a href="userdash.php">&larr; Back to Dashboard</a>
            <a href="index.php">Go to Home</a>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

</body>
</html>