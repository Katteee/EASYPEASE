<?php
require __DIR__ . '/includes/catalog.php';
$q = catalog_text($_GET, 'q');
$genre = catalog_text($_GET, 'genre');
$author = catalog_text($_GET, 'author');
$availability = catalog_text($_GET, 'availability');
$sort = catalog_text($_GET, 'sort', 'title');
$yearFrom = catalog_text($_GET, 'year_from');
$yearTo = catalog_text($_GET, 'year_to');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$orders = ['title' => 'title ASC, id ASC', 'author' => 'author ASC, title ASC, id ASC', 'newest' => 'year_published DESC, title ASC, id ASC'];
$error = '';
$filtered = $genres = $authors = $activeBooks = [];
$total = 0;
$pages = 1;
$perPage = 12;
if ($page === false || !isset($orders[$sort]) || !in_array($availability, ['', 'available', 'unavailable'], true) || mb_strlen($q) > 200 || mb_strlen($author) > 150 || mb_strlen($genre) > 50) {
    $error = 'Neplatné vyhledávání nebo filtr.';
}
foreach ([$yearFrom, $yearTo] as $year) {
    if ($year !== '' && filter_var($year, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2100]]) === false) $error = 'Rok musí být číslo od 1 do 2100.';
}
if ($yearFrom !== '' && $yearTo !== '' && (int)$yearFrom > (int)$yearTo) $error = 'Rok od nesmí být větší než rok do.';
if ($error) http_response_code(400);
try {
    catalog_user($pdo);
    $stmt = $pdo->prepare("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre <> '' ORDER BY genre");
    $stmt->execute(); $genres = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $pdo->prepare("SELECT DISTINCT author FROM books ORDER BY author");
    $stmt->execute(); $authors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$error) {
        $conditions = $params = [];
        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $isbnExpression = "REPLACE(REPLACE(COALESCE(isbn, ''), '-', ''), ' ', '')";
        $normalized = preg_replace('/[\s-]+/', '', $q);
        if ($normalized !== '' && preg_match('/^[0-9Xx]+$/D', $normalized)) {
            $conditions[] = "(title LIKE ? OR author LIKE ? OR $isbnExpression LIKE ?)";
            array_push($params, '%' . $q . '%', '%' . $q . '%', '%' . $normalized . '%');
        } else {
            foreach ($words as $word) {
                $pattern = '%' . strtr($word, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
                $conditions[] = "(title LIKE ? ESCAPE '!' OR author LIKE ? ESCAPE '!' OR isbn LIKE ? ESCAPE '!')";
                array_push($params, $pattern, $pattern, $pattern);
            }
        }
        if ($genre !== '') { $conditions[] = 'genre = ?'; $params[] = $genre; }
        if ($author !== '') { $conditions[] = 'author = ?'; $params[] = $author; }
        if ($availability === 'available') $conditions[] = 'available_copies > 0';
        if ($availability === 'unavailable') $conditions[] = 'available_copies = 0';
        if ($yearFrom !== '') { $conditions[] = 'year_published >= ?'; $params[] = (int)$yearFrom; }
        if ($yearTo !== '') { $conditions[] = 'year_published <= ?'; $params[] = (int)$yearTo; }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM books' . $where);
        $stmt->execute($params); $total = (int)$stmt->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $stmt = $pdo->prepare('SELECT * FROM books' . $where . ' ORDER BY ' . $orders[$sort] . ' LIMIT ? OFFSET ?');
        $stmt->execute(array_merge($params, [$perPage, ($page - 1) * $perPage]));
        $filtered = $stmt->fetchAll();
        $activeBooks = catalog_active($pdo, array_column($filtered, 'id'));
    }
} catch (PDOException $e) {
    error_log((string)$e); http_response_code(503);
    $error = 'Katalog se nepodařilo načíst. Zkuste to později.';
}
function catalog_page_link(int $number): string {
    global $q, $genre, $author, $availability, $sort, $yearFrom, $yearTo;
    return 'books.php?' . http_build_query(['q'=>$q, 'genre'=>$genre, 'author'=>$author, 'availability'=>$availability, 'sort'=>$sort, 'year_from'=>$yearFrom, 'year_to'=>$yearTo, 'page'=>$number]);
}
catalog_header('Katalog knih');
?>
<form method="get" class="card search-grid" action="books.php">
<div class="search-wide"><label for="q">Název, autor nebo ISBN</label><input class="form-control" type="search" id="q" name="q" maxlength="200" value="<?= h($q) ?>" placeholder="Například Orwell 1984"></div>
<div><label for="genre">Žánr</label><select class="form-control" id="genre" name="genre"><option value="">Všechny žánry</option><?php foreach ($genres as $item): ?><option value="<?= h($item) ?>" <?= $genre === $item ? 'selected' : '' ?>><?= h($item) ?></option><?php endforeach; ?></select></div>
<div><label for="author">Autor</label><select class="form-control" id="author" name="author"><option value="">Všichni autoři</option><?php foreach ($authors as $item): ?><option value="<?= h($item) ?>" <?= $author === $item ? 'selected' : '' ?>><?= h($item) ?></option><?php endforeach; ?></select></div>
<div><label for="availability">Dostupnost</label><select class="form-control" id="availability" name="availability"><?php foreach ([''=>'Všechny knihy','available'=>'Pouze dostupné','unavailable'=>'Pouze nedostupné'] as $value=>$label): ?><option value="<?= h($value) ?>" <?= $availability === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
<div><label for="year_from">Rok od</label><input class="form-control" type="number" min="1" max="2100" id="year_from" name="year_from" value="<?= h($yearFrom) ?>"></div>
<div><label for="year_to">Rok do</label><input class="form-control" type="number" min="1" max="2100" id="year_to" name="year_to" value="<?= h($yearTo) ?>"></div>
<div><label for="sort">Řazení</label><select class="form-control" id="sort" name="sort"><?php foreach (['title'=>'Podle názvu','author'=>'Podle autora','newest'=>'Rok vydání sestupně'] as $value=>$label): ?><option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
<div class="actions search-wide"><button class="btn btn-primary">Vyhledat</button><a class="btn btn-secondary" href="books.php">Vymazat filtry</a></div>
</form>
<?php if ($error): ?><p class="alert alert-error" role="alert"><?= h($error) ?></p><?php else: ?>
<p class="section" role="status">Nalezeno knih: <?= $total ?></p>
<?php if (!$filtered): ?><div class="card">Žádné knihy neodpovídají hledání. Zkuste jiná slova nebo vymažte filtry.</div><?php endif; ?>
<div class="book-grid"><?php foreach ($filtered as $book): ?><article class="card book-card"><div class="book-symbol" aria-hidden="true">📖</div><p class="muted"><?= h($book['genre'] ?: 'Bez žánru') ?></p><h2><a href="book.php?book_id=<?= (int)$book['id'] ?>"><?= h($book['title']) ?></a></h2><p><?= h($book['author']) ?></p><p><?= h($book['year_published'] ?? 'Rok neuveden') ?></p><p class="badge <?= remaining($book) ? 'badge-success' : 'badge-danger' ?>">Dostupné: <?= remaining($book) ?> / <?= (int)$book['total_copies'] ?></p><div class="actions"><a class="btn btn-primary" href="book.php?book_id=<?= (int)$book['id'] ?>">Detail knihy</a><?php reserve_button($book); ?></div></article><?php endforeach; ?></div>
<?php if ($pages > 1): ?><nav class="catalog-pagination" aria-label="Stránkování katalogu"><?php if ($page > 1): ?><a class="btn btn-secondary" href="<?= h(catalog_page_link($page - 1)) ?>">← Předchozí</a><?php endif; ?><span>Strana <?= $page ?> z <?= $pages ?></span><?php if ($page < $pages): ?><a class="btn btn-secondary" href="<?= h(catalog_page_link($page + 1)) ?>">Další →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; catalog_footer(); ?>
