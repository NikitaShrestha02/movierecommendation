<?php 
    // Session-scoped cookies: the login lasts only for the current browser
    // session and is dropped when the browser/tab session ends -- no 30-day
    // "remember me" cookie is written anymore.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    $conn = mysqli_connect('localhost', 'root', '', 'movie_db');
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    

    if (isset($_POST['submit'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $result = mysqli_query($conn, "SELECT * FROM user WHERE email = '$email' AND Password = '$password'");

        if ($result) {
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                session_regenerate_id(true); // new session id on login
                $_SESSION["uemail"] = $row['email'];
                $_SESSION["show_mood_modal"] = true;
                $_SESSION["fresh_login"] = true; // lets the landing tab authorise itself
                // Session cookie (expires => 0): cleared when the browser closes.
                setcookie('uemail', $row['email'], [
                    'expires'  => 0,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                header('Location: index.php');
                exit();
            }
            else {
                $error = "Invalid email or password";
                $_SESSION["login_error"] = $error;
                header("Location: nlogin.php");
                exit();
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Movie Recommendation</title>
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
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .login-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 420px;
            padding: 36px 30px;
            text-align: left;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 22px;
        }

        .login-brand img {
            height: 42px;
            width: auto;
            display: inline-block;
            margin-bottom: 8px;
        }

        .login-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #60a5fa;
            background-color: rgba(37, 99, 235, 0.12);
            border: 1px solid rgba(96, 165, 250, 0.25);
            padding: 3px 9px;
            border-radius: 4px;
        }

        .login-title {
            color: #f8fafc;
            font-size: 22px;
            font-weight: 700;
            margin: 12px 0 6px 0;
            text-align: center;
            letter-spacing: -0.01em;
        }

        .login-subtitle {
            color: #94a3b8;
            font-size: 13.5px;
            text-align: center;
            margin: 0 0 24px 0;
        }

        .alert {
            padding: 11px 14px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.45;
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

        .login-btn {
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

        .login-btn:hover {
            background-color: #1d4ed8;
        }

        .card-links {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #242e40;
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        .card-links a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .card-links a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        .admin-link {
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-brand">
            <img src="LOGOO.png" alt="Logo">
            <div><span class="login-badge">User Portal</span></div>
        </div>

        <h1 class="login-title">Sign In to Your Account</h1>
        <p class="login-subtitle">Enter your credentials to access your personalized movies</p>

        <?php
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            echo "<div class='alert alert-success'>You have been successfully logged out.</div>";
        }
        if (isset($_SESSION["login_error"]) && !empty($_SESSION["login_error"])) {
            echo "<div class='alert alert-error'>" . htmlspecialchars($_SESSION['login_error']) . "</div>";
            $_SESSION["login_error"] = "";
        }
        ?>

        <form id="loginForm" method="post">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" name="submit" class="login-btn">Sign In</button>
        </form>

        <div class="card-links">
            <div>Don't have an account? <a href="signup.php">Sign Up</a></div>
            <div class="admin-link">Are you an administrator? <a href="adform.php">Admin Login</a></div>
        </div>
    </div>
</body>
</html>