
<?php
// Session security settings must be applied before session_start().
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isHttps = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function destroy_user_session(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    session_destroy();
}

// Timeout after 20 minutes of inactivity.
if (isset($_SESSION['user_id'])) {
    if (
        isset($_SESSION['LAST_ACTIVITY']) &&
        time() - $_SESSION['LAST_ACTIVITY'] > 1200
    ) {
        destroy_user_session();
        header('Location: /login.php?msg=timeout');
        exit;
    }

    $_SESSION['LAST_ACTIVITY'] = time();
}

// Protect pages that require authentication.
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}
