<?php
require __DIR__ . '/includes/catalog.php';
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$genre = is_string($_GET['genre'] ?? null) ? $_GET['genre'] : '';
$error = '';
$genres = [];
$filtered = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre <> '' ORDER BY genre");
    $stmt->execute();
    $genres = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $conditions = [];
    $params = [];
    if ($q !== '') {

        $pattern = '%' . strtr($q, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $conditions[] = "(title LIKE ? ESCAPE '!' OR author LIKE ? ESCAPE '!' OR isbn LIKE ? ESCAPE '!')";
        $params = [$pattern, $pattern, $pattern];
    }
    if ($genre !== '') { $conditions[] = 'genre = ?'; $params[] = $genre; }
    $sql = 'SELECT * FROM books';
    if ($conditions) $sql .= ' WHERE ' . implode(' AND ', $conditions);
    $sql .= ' ORDER BY title, id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filtered = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log((string)$e);
    http_response_code(503);
    $error = 'Katalog se nepodařilo načíst. Zkuste to později.';
}
catalog_header('Katalog knih');
?>
<form method="get" class="card search-grid"><div><label for="q">Název, autor nebo ISBN</label><input class="form-control" type="search" id="q" name="q" value="<?= h($q) ?>" placeholder="Vyhledat knihu"></div><div><label for="genre">Žánr</label><select class="form-control" id="genre" name="genre"><option value="">Všechny žánry</option><?php foreach ($genres as $item): ?><option value="<?= h($item) ?>" <?= $genre === $item ? 'selected' : '' ?>><?= h($item) ?></option><?php endforeach; ?></select></div><button class="btn btn-primary">Vyhledat</button><a class="btn btn-secondary" href="books.php">Vymazat</a></form>
<p class="section" role="status">Nalezeno knih: <?= count($filtered) ?></p>
<?php if ($error): ?><p class="alert alert-error" role="alert"><?= h($error) ?></p><?php elseif (!$filtered): ?><div class="card">Žádné knihy neodpovídají hledání. Zkuste jiný název nebo žánr.</div><?php endif; ?>
<div class="book-grid"><?php foreach ($filtered as $book): ?><article class="card book-card"><div class="book-symbol" aria-hidden="true">📖</div><p class="muted"><?= h($book['genre']) ?></p><h2><a href="book.php?book_id=<?= $book['id'] ?>"><?= h($book['title']) ?></a></h2><p><?= h($book['author']) ?></p><p class="badge <?= remaining($book) ? 'badge-success' : 'badge-danger' ?>">Dostupné: <?= remaining($book) ?> / <?= $book['total_copies'] ?></p><div class="actions"><a class="btn btn-primary" href="book.php?book_id=<?= $book['id'] ?>">Detail knihy</a><?php reserve_button($book); ?></div></article><?php endforeach; ?></div>
<?php catalog_footer(); ?>
