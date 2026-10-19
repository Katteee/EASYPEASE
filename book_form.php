<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Přístup odepřen.'); }
require_once __DIR__ . '/../config/db.php';
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$_SESSION['admin_books_csrf'] ??= bin2hex(random_bytes(32));
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) : null;
if ($id === false) { http_response_code(400); exit('Neplatné ID.'); }
$editing = $id !== null;
$book = ['title'=>'','author'=>'','isbn'=>'','genre'=>'','year_published'=>'','description'=>'','total_copies'=>1,'available_copies'=>1];
if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?'); $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { http_response_code(404); exit('Kniha nebyla nalezena.'); }
    $book = $found;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['admin_books_csrf'], $token)) { http_response_code(403); exit('Neplatný bezpečnostní token.'); }
    foreach (['title','author','isbn','genre','year_published','description','total_copies'] as $field) {
        $book[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    if ($book['title'] === '' || mb_strlen($book['title']) > 255) $errors[] = 'Název musí mít 1 až 255 znaků.';
    if ($book['author'] === '' || mb_strlen($book['author']) > 150) $errors[] = 'Autor musí mít 1 až 150 znaků.';
    if ($book['isbn'] !== '' && (strlen($book['isbn']) > 20 || !preg_match('/^[0-9Xx-]+$/', $book['isbn']))) $errors[] = 'Neplatné ISBN.';
    if (mb_strlen($book['genre']) > 50) $errors[] = 'Žánr je příliš dlouhý.';
    $year = $book['year_published'] === '' ? null : filter_var($book['year_published'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>2100]]);
    if ($year === false) $errors[] = 'Neplatný rok vydání.';
    $total = filter_var($book['total_copies'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
    if ($total === false) $errors[] = 'Počet kusů musí být nezáporné celé číslo.';
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            if ($editing) {
                $stmt = $pdo->prepare('SELECT total_copies, available_copies FROM books WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]); $current = $stmt->fetch();
                if (!$current) throw new DomainException('Kniha již neexistuje.');
                $borrowed = (int)$current['total_copies'] - (int)$current['available_copies'];
                if ($total < $borrowed) throw new DomainException('Počet kusů nesmí být menší než počet rezervovaných kusů (' . $borrowed . ').');
                $stmt = $pdo->prepare('UPDATE books SET title=?, author=?, isbn=?, genre=?, year_published=?, description=?, total_copies=?, available_copies=? WHERE id=?');
                $stmt->execute([$book['title'],$book['author'],$book['isbn'] ?: null,$book['genre'] ?: null,$year,$book['description'],$total,$total-$borrowed,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO books (title,author,isbn,genre,year_published,description,total_copies,available_copies) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$book['title'],$book['author'],$book['isbn'] ?: null,$book['genre'] ?: null,$year,$book['description'],$total,$total]);
            }
            $pdo->commit();
            $_SESSION['admin_books_notice'] = $editing ? 'Kniha byla upravena.' : 'Kniha byla přidána.';
            header('Location: books.php', true, 303); exit;
        } catch (DomainException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack(); $errors[] = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack(); error_log((string)$ex);
            $errors[] = $ex->getCode() === '23000' ? 'ISBN již existuje nebo jsou data neplatná.' : 'Knihu se nepodařilo uložit.';
        }
    }
}
?>
<!DOCTYPE html><html lang="cs"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $editing ? 'Upravit knihu' : 'Přidat knihu' ?> | Knihovna</title><link rel="stylesheet" href="../css/style.css"></head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main><div class="container"><h1 class="page-title"><?= $editing ? 'Upravit knihu' : 'Přidat novou knihu' ?></h1>
<?php foreach ($errors as $error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endforeach; ?>
<div class="card form-container"><form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['admin_books_csrf']) ?>">
<?php foreach (['title'=>'Název knihy','author'=>'Autor','isbn'=>'ISBN','genre'=>'Žánr','year_published'=>'Rok vydání','total_copies'=>'Počet kusů'] as $field=>$label): ?>
<div class="form-group"><label for="<?= $field ?>"><?= e($label) ?></label><input class="form-control" id="<?= $field ?>" name="<?= $field ?>" type="<?= in_array($field,['year_published','total_copies'],true) ? 'number' : 'text' ?>" <?= in_array($field,['title','author','total_copies'],true) ? 'required' : '' ?> <?= $field === 'total_copies' ? 'min="0"' : '' ?> value="<?= e($book[$field] ?? '') ?>"></div>
<?php endforeach; ?>
<div class="form-group"><label for="description">Popis</label><textarea class="form-control" id="description" name="description"><?= e($book['description'] ?? '') ?></textarea></div>
<button class="btn btn-success" type="submit">Uložit knihu</button> <a class="btn btn-secondary" href="books.php">Zpět</a></form></div></div></main></body></html>
