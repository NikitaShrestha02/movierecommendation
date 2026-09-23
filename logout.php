<?php
    session_start();

    // Check if the current session belongs to the admin
    $wasAdmin = isset($_SESSION['uemail']) && $_SESSION['uemail'] === 'snadmin@gmail.com';

    // Clear all session data.
    $_SESSION = [];

    // Expire the PHP session cookie (PHPSESSID) itself.
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'] ?: '/',
            'domain'   => $p['domain'] ?? '',
            'secure'   => $p['secure'] ?? false,
            'httponly' => $p['httponly'] ?? true,
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }

    // Expire the app's own uemail cookie.
    setcookie('uemail', '', [
        'expires'  => time() - 42000,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_destroy();

    if ($wasAdmin) {
        header('Location: adform.php?action=logout');
    } else {
        header('Location: nlogin.php?action=logout');
    }
    exit();
?>