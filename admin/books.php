<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Přístup odepřen.'); }
require_once __DIR__ . '/../config/db.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$_SESSION['admin_books_csrf'] ??= bin2hex(random_bytes(32));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['admin_books_csrf'], $token)) {
        http_response_code(403); $error = 'Neplatný bezpečnostní token.';
    } else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null || ($_POST['action'] ?? '') !== 'delete') {
            http_response_code(400); $error = 'Neplatný požadavek.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
                $stmt->execute([$id]);
                $_SESSION['admin_books_notice'] = $stmt->rowCount() ? 'Kniha byla odstraněna.' : 'Kniha nebyla nalezena.';
                header('Location: books.php', true, 303); exit;
            } catch (PDOException $ex) {
                error_log((string)$ex);
                $error = $ex->getCode() === '23000' ? 'Knihu s historií rezervací nelze odstranit.' : 'Knihu se nepodařilo odstranit.';
            }
        }
    }
}
try { $books = $pdo->query('SELECT id, title, author, isbn, genre, total_copies, available_copies FROM books ORDER BY id DESC')->fetchAll(); }
catch (PDOException $ex) { error_log((string)$ex); $books = []; $error = 'Knihy se nepodařilo načíst.'; }
$notice = $_SESSION['admin_books_notice'] ?? '';
unset($_SESSION['admin_books_notice']);
?>
<!DOCTYPE html>
<html lang="cs"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administrace knih | Knihovna</title><link rel="stylesheet" href="../css/style.css"></head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main><div class="container"><h1 class="page-title">Administrace knih</h1>
<div class="section"><a class="btn btn-primary" href="book_form.php">+ Přidat knihu</a></div>
<?php if ($notice): ?><div class="alert alert-success" role="status"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="card"><h2>Seznam knih</h2><div class="table-wrapper"><table class="table"><thead><tr><th>ID</th><th>Název</th><th>Autor</th><th>ISBN</th><th>Žánr</th><th>Počet kusů</th><th>Dostupné</th><th>Akce</th></tr></thead><tbody>
<?php foreach ($books as $book): ?><tr><td><?= (int)$book['id'] ?></td><td><?= e($book['title']) ?></td><td><?= e($book['author']) ?></td><td><?= e($book['isbn'] ?? '—') ?></td><td><?= e($book['genre'] ?? '—') ?></td><td><?= (int)$book['total_copies'] ?></td><td><?= (int)$book['available_copies'] ?></td><td><div class="actions"><a class="btn btn-secondary btn-small" href="book_form.php?id=<?= (int)$book['id'] ?>">Upravit</a><form method="post" onsubmit="return confirm('Opravdu odstranit tuto knihu?');"><input type="hidden" name="csrf" value="<?= e($_SESSION['admin_books_csrf']) ?>"><input type="hidden" name="id" value="<?= (int)$book['id'] ?>"><button class="btn btn-danger btn-small" name="action" value="delete">Odstranit</button></form></div></td></tr><?php endforeach; ?>
<?php if (!$books): ?><tr><td colspan="8">Žádné knihy k zobrazení.</td></tr><?php endif; ?>
</tbody></table></div></div></div></main></body></html>
