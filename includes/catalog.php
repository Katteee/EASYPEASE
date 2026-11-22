<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$_SESSION['catalog_csrf'] ??= bin2hex(random_bytes(32));
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function remaining($book): int { return max(0, min((int)$book['total_copies'], (int)$book['available_copies'])); }
function catalog_text(array $input, string $key, string $default = ''): string {
    return is_string($input[$key] ?? null) ? trim($input[$key]) : $default;
}
function catalog_user(PDO $pdo): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $stmt = $pdo->prepare('SELECT id, login, role FROM users WHERE id = ?');
    $stmt->execute([$id === false ? 0 : $id]);
    $user = $stmt->fetch();
    if (!$user) {
        destroy_user_session();
        header('Location: login.php');
        exit;
    }
    return $user;
}
function catalog_active(PDO $pdo, array $ids): array {
    if (!isset($_SESSION['user_id']) || !$ids) return [];
    $stmt = $pdo->prepare("SELECT book_id FROM reservations WHERE user_id = ? AND status = 'active' AND book_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ')');
    $stmt->execute(array_merge([(int)$_SESSION['user_id']], $ids));
    return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
}
function catalog_header(string $title): void {
    global $pdo;
?>
<!DOCTYPE html>
<html lang="cs"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= h($title) ?> | Knihovna</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/catalog.css"></head>
<body class="catalog-page">
<a class="catalog-skip" href="#catalog-main">Přejít na obsah</a>
<?php include __DIR__ . '/navbar.php'; ?>
<main id="catalog-main"><div class="container"><h1 class="page-title"><?= h($title) ?></h1>
<?php }
function catalog_footer(): void { echo '</div></main></body></html>'; }
function reserve_button(array $book): void {
    global $activeBooks;
    if (isset($activeBooks[$book['id']])) {
        echo '<a class="btn btn-secondary" href="reservations.php">Již rezervováno</a>';
    } elseif (remaining($book) < 1) {
        echo '<button class="btn btn-secondary" disabled>Nedostupná</button>';
    } elseif (!isset($_SESSION['user_id'])) {
        echo '<a class="btn btn-primary" href="login.php">Přihlásit se pro rezervaci</a>';
    } else {
?>
<form method="post" action="reservations.php"><input type="hidden" name="csrf" value="<?= h($_SESSION['catalog_csrf']) ?>"><input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>"><button class="btn btn-success" name="action" value="reserve">Rezervovat</button></form>
<?php
    }
}
