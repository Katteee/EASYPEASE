<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$_SESSION['catalog_csrf'] = $_SESSION['catalog_csrf'] ?? bin2hex(random_bytes(32));
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function remaining($book) { return max(0, (int)$book['available_copies']); }
function catalog_header($title) {
?>
<!DOCTYPE html><html lang="cs"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= h($title) ?> | Knihovna</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/catalog.css"></head><body>
<?php

ob_start();
include __DIR__ . '/navbar.php';
$catalogNavbar = ob_get_clean();
$catalogNavbar = str_replace(['href="../', "href='../"], ['href="', "href='"], $catalogNavbar);

echo str_replace(['href="index.php"', "href='index.php'"], ['href="books.php"', "href='books.php'"], $catalogNavbar);
?>
<main><div class="container"><h1 class="page-title"><?= h($title) ?></h1>
<?php }
function catalog_footer() { echo '</div></main></body></html>'; }
function reserve_button($book) {
 if (!isset($_SESSION['user_id'])) { echo '<a class="btn btn-primary" href="login.php">Přihlásit se pro rezervaci</a>'; return; }
 if (remaining($book) < 1) { echo '<button class="btn btn-secondary" disabled>Nedostupná</button>'; return; }
?>
<form method="post" action="reservations.php"><input type="hidden" name="csrf" value="<?= h($_SESSION['catalog_csrf']) ?>"><input type="hidden" name="book_id" value="<?= $book['id'] ?>"><button class="btn btn-success" name="action" value="reserve">Rezervovat</button></form>
<?php }
