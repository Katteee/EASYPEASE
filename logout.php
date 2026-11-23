<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Очищаем данные сессии
$_SESSION = [];

// Удаляем cookie сессии в браузере
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Уничтожаем сессию на сервере
session_destroy();

header("Location: login.php");
exit();