<?php
require __DIR__ . '/includes/catalog.php';
$id = filter_var($_GET['book_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$book = null;
$error = '';
$activeBooks = [];
try {
    catalog_user($pdo);
    if ($id !== false) {
        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        if ($book) $activeBooks = catalog_active($pdo, [$id]);
    }
} catch (PDOException $e) {
    error_log((string)$e); http_response_code(503);
    $error = 'Detail knihy se nepodařilo načíst. Zkuste to později.';
}
if (!$book && !$error) http_response_code(404);
catalog_header($book ? $book['title'] : 'Kniha nenalezena');
?>
<div class="actions section"><a class="btn btn-secondary" href="books.php">← Zpět do katalogu</a><?php if (isset($_SESSION['user_id'])): ?><a class="btn btn-secondary" href="reservations.php">Moje rezervace</a><?php endif; ?></div>
<?php if (!$book): ?><div class="alert alert-error" role="alert"><?= h($error ?: 'Požadovaná kniha neexistuje.') ?></div><?php else: ?>
<article class="card"><h2><?= h($book['title']) ?></h2><dl class="book-meta"><dt>Autor</dt><dd><?= h($book['author']) ?></dd><dt>ISBN</dt><dd><?= h($book['isbn'] ?: 'Neuvedeno') ?></dd><dt>Žánr</dt><dd><?= h($book['genre'] ?: 'Neuvedeno') ?></dd><dt>Rok vydání</dt><dd><?= h($book['year_published'] ?? 'Neuvedeno') ?></dd><dt>Dostupnost</dt><dd><?= remaining($book) ?> z <?= (int)$book['total_copies'] ?> kusů</dd></dl><h3>O knize</h3><p class="section book-description"><?= h($book['description'] ?: 'Popis zatím není k dispozici.') ?></p><?php reserve_button($book); ?></article>
<?php endif; catalog_footer(); ?>
