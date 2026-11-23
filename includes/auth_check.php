<?php
// Безопасные настройки cookie для сессии (защита от XSS и CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 1. Защита от Session Hijacking (привязка сессии к User-Agent браузера)
$currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['USER_AGENT'])) {
        $_SESSION['USER_AGENT'] = $currentUserAgent;
    } elseif ($_SESSION['USER_AGENT'] !== $currentUserAgent) {
        session_unset();
        session_destroy();
        header("Location: /login.php?msg=security");
        exit();
    }
}

// 2. Автоматический логаут после 20 минут неактивности (1200 секунд)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /login.php?msg=timeout");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 3. Проверка авторизации (доступ только для вошедших пользователей)
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }
}

// 4. Проверка прав администратора (доступ только для роли admin)
function require_admin(): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        die("Přístup odepřen (403 Forbidden). Tato stránka je pouze pro administrátory.");
    }
}