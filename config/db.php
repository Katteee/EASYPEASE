<?php
// Параметры подключения к базе данных MySQL (XAMPP)
$host    = '127.0.0.1';
$db      = 'library_system';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Выброс исключений при ошибках SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ассоциативные массивы по умолчанию
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Настоящие Prepared Statements (защита от SQL Injection)
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}