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
                $_SESSION["just_logged_in"] = $row['name'] ?? $row['email']; // for login toast
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
        /* ── Toast Notifications ── */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #1a2438;
            border-radius: 10px;
            padding: 14px 16px 18px;
            min-width: 300px;
            max-width: 360px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.45);
            pointer-events: all;
            position: relative;
            overflow: hidden;
            animation: toastSlideIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        .toast.toast-success { border: 1px solid rgba(34,197,94,0.3); }
        .toast.toast-error   { border: 1px solid rgba(239,68,68,0.3); }

        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateX(60px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes toastSlideOut {
            to   { opacity: 0; transform: translateX(80px); }
        }
        .toast.hiding {
            animation: toastSlideOut 0.3s ease forwards;
        }
        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .toast-body { flex: 1; }
        .toast-title {
            color: #f8fafc;
            font-size: 13.5px;
            font-weight: 700;
            margin: 0 0 3px;
        }
        .toast-msg {
            color: #94a3b8;
            font-size: 12.5px;
            margin: 0;
            line-height: 1.4;
        }
        .toast-close {
            background: none; border: none;
            color: #64748b; font-size: 16px;
            cursor: pointer; padding: 0;
            line-height: 1; flex-shrink: 0;
            transition: color 0.15s;
        }
        .toast-close:hover { color: #f1f5f9; }
        .toast-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px; width: 100%;
            border-radius: 0 0 10px 10px;
        }
        .toast-success .toast-progress { background: linear-gradient(90deg,#16a34a,#22c55e); }
        .toast-error   .toast-progress { background: linear-gradient(90deg,#b91c1c,#ef4444); }
        .toast-progress-fill {
            height: 100%; width: 100%;
            animation: toastProgress 4s linear forwards;
        }
        .toast-success .toast-progress-fill { background: rgba(34,197,94,0.25); }
        .toast-error   .toast-progress-fill { background: rgba(239,68,68,0.25); }
        @keyframes toastProgress {
            from { width: 100%; }
            to   { width: 0%; }
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
        $toasts = [];
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            $toasts[] = ['type' => 'success', 'title' => 'Logged Out', 'msg' => 'You have been successfully logged out.'];
        }
        if (isset($_SESSION["login_error"]) && !empty($_SESSION["login_error"])) {
            $toasts[] = ['type' => 'error', 'title' => 'Login Failed', 'msg' => htmlspecialchars($_SESSION['login_error'])];
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

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
var toasts = <?php echo json_encode($toasts ?? []); ?>;
var icons  = { success: '✅', error: '❌' };

function dismissToast(el) {
    el.classList.add('hiding');
    setTimeout(function() { el.remove(); }, 300);
}

toasts.forEach(function(t, i) {
    setTimeout(function() {
        var el = document.createElement('div');
        el.className = 'toast toast-' + t.type;
        el.innerHTML =
            '<span class="toast-icon">' + icons[t.type] + '</span>' +
            '<div class="toast-body">' +
                '<p class="toast-title">' + t.title + '</p>' +
                '<p class="toast-msg">' + t.msg + '</p>' +
            '</div>' +
            '<button class="toast-close" onclick="dismissToast(this.parentElement)">&times;</button>' +
            '<div class="toast-progress"><div class="toast-progress-fill"></div></div>';
        document.getElementById('toastContainer').appendChild(el);
        setTimeout(function() { dismissToast(el); }, 4000);
    }, i * 200);
});
</script>

</body>
</html>
