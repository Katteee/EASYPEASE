<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_check.php';
require_login();
$stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([(int)$_SESSION['user_id']]);
if ($stmt->fetchColumn() !== 'admin') { http_response_code(403); exit('Přístup odepřen.'); }
function admin_h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function admin_csrf(): string { return $_SESSION['admin_csrf'] ??= bin2hex(random_bytes(32)); }
function admin_check_csrf(): void { if (!hash_equals(admin_csrf(), (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Neplatný CSRF token.'); } }
