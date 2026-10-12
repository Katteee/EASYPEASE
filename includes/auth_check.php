<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Автоматический логаут после 20 минут неактивности (1200 секунд)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=timeout");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Функция для закрытия страниц от неавторизованных пользователей
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}