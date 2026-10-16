<?php
require __DIR__ . '/includes/catalog.php';
$id = filter_var($_GET['book_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$book = null;
$error = '';
if ($id !== false) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
        $stmt->execute([$id]);
        $book = $stmt->fetch();
    } catch (PDOException $e) {
        error_log((string)$e);
        $error = 'Detail knihy se nepodařilo načíst. Zkuste to později.';
        http_response_code(503);
    }
}
if (!$book && !$error) http_response_code(404);
catalog_header($book ? $book['title'] : 'Kniha nenalezena');
?>
<a class="btn btn-secondary section" href="books.php">← Zpět do katalogu</a>
<?php if (!$book): ?><div class="alert alert-error"><?= h($error ?: 'Požadovaná kniha neexistuje.') ?></div><?php else: ?>
<article class="card"><h2><?= h($book['title']) ?></h2><dl class="book-meta"><dt>Autor</dt><dd><?= h($book['author']) ?></dd><dt>ISBN</dt><dd><?= h($book['isbn'] ?? 'Neuvedeno') ?></dd><dt>Žánr</dt><dd><?= h($book['genre']) ?></dd><dt>Rok vydání</dt><dd><?= h($book['year_published'] ?? 'Neuvedeno') ?></dd><dt>Dostupnost</dt><dd><?= remaining($book) ?> z <?= $book['total_copies'] ?> kusů</dd></dl><h3>O knize</h3><p class="section"><?= h($book['description']) ?></p><?php reserve_button($book); ?></article>
<?php endif; catalog_footer(); ?>
