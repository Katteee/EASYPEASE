<?php
require __DIR__ . '/includes/catalog.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/includes/reservation_service.php';
$error = $listError = '';
$reservations = [];
$user = null;
$isAdmin = false;
$scope = catalog_text($_GET, 'scope') === 'all' ? 'all' : 'mine';
$status = catalog_text($_GET, 'status');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$labels = ['active'=>'Aktivní', 'cancelled'=>'Zrušená', 'completed'=>'Dokončená'];
$notice = is_string($_SESSION['reservation_notice'] ?? null) ? $_SESSION['reservation_notice'] : '';
unset($_SESSION['reservation_notice']);
try {
    $user = catalog_user($pdo);
    $isAdmin = $user && $user['role'] === 'admin';
} catch (PDOException $e) {
    error_log((string)$e); http_response_code(503);
    $error = 'Účet se nepodařilo ověřit. Zkuste to později.';
}
if ($scope === 'all' && !$isAdmin && !$error) {
    http_response_code(403);
    $error = 'Správa všech rezervací je dostupná pouze administrátorovi.';
}
if (($status !== '' && !isset($labels[$status])) || $page === false) {
    http_response_code(400); $error = 'Neplatný filtr nebo číslo stránky.';
}
$query = http_build_query(['scope'=>$scope, 'status'=>$status]);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $token = $_POST['csrf'] ?? null;
    $bookId = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $reservationId = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $action = catalog_text($_POST, 'action');
    if (!is_string($token) || !hash_equals($_SESSION['catalog_csrf'], $token)) {
        http_response_code(403); $error = 'Neplatný formulář. Obnovte stránku.';
    } elseif ($bookId === false || !in_array($action, ['reserve','cancel','complete'], true) || ($action !== 'reserve' && $reservationId === false)) {
        http_response_code(400); $error = 'Neplatné ID nebo akce.';
    } else {
        try {
            change_reservation($pdo, (int)$user['id'], $bookId, $action, $action === 'reserve' ? null : $reservationId);
            $_SESSION['reservation_notice'] = ['reserve'=>'Kniha byla rezervována.', 'cancel'=>'Rezervace byla zrušena.', 'complete'=>'Rezervace byla dokončena a kus knihy byl vrácen.'][$action];
            header('Location: reservations.php?' . $query, true, 303);
            exit;
        } catch (DomainException $e) {
            http_response_code(in_array($e->getCode(), [400,401,403,404,409], true) ? $e->getCode() : 409);
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log((string)$e); http_response_code(503);
            $error = 'Změnu se nepodařilo uložit. Obnovte stránku a zkuste to znovu.';
        }
    }
}
$total = 0;
$pages = 1;
if ($user && ($scope !== 'all' || $isAdmin) && ($status === '' || isset($labels[$status])) && $page !== false) {
    try {
        $conditions = $params = [];
        if ($scope !== 'all') { $conditions[] = 'r.user_id = ?'; $params[] = (int)$user['id']; }
        if ($status !== '') { $conditions[] = 'r.status = ?'; $params[] = $status; }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations r' . $where);
        $stmt->execute($params); $total = (int)$stmt->fetchColumn();
        $pages = max(1, (int)ceil($total / 20)); $page = min($page, $pages);
        $stmt = $pdo->prepare('SELECT r.id, r.user_id, r.book_id, r.status, r.created_at, b.title, u.login FROM reservations r JOIN books b ON b.id=r.book_id JOIN users u ON u.id=r.user_id' . $where . ' ORDER BY r.created_at DESC, r.id DESC LIMIT ? OFFSET ?');
        $stmt->execute(array_merge($params, [20, ($page - 1) * 20]));
        $reservations = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log((string)$e); http_response_code(503);
        $listError = 'Rezervace se nepodařilo načíst. Zkuste to později.';
    }
}
catalog_header($scope === 'all' ? 'Správa rezervací' : 'Moje rezervace');
?>
<div class="actions section"><a class="btn btn-primary" href="books.php">Procházet katalog</a><?php if ($isAdmin): ?><a class="btn btn-secondary" href="reservations.php<?= $scope === 'all' ? '' : '?scope=all' ?>"><?= $scope === 'all' ? 'Moje rezervace' : 'Správa všech rezervací' ?></a><a class="btn btn-secondary" href="admin/books.php">Administrace knih</a><?php endif; ?></div>
<?php if ($notice): ?><p class="alert alert-success" role="status"><?= h($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="alert alert-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if ($user && ($scope !== 'all' || $isAdmin)): ?>
<form method="get" action="reservations.php" class="card reservation-filters"><input type="hidden" name="scope" value="<?= h($scope) ?>"><div><label for="status">Stav rezervace</label><select class="form-control" name="status" id="status"><option value="">Všechny stavy</option><?php foreach ($labels as $value=>$label): ?><option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div><button class="btn btn-primary">Filtrovat</button></form>
<?php if ($listError): ?><p class="alert alert-error" role="alert"><?= h($listError) ?></p><?php elseif (!$reservations): ?><div class="card"><h2>Žádné rezervace k zobrazení</h2><p>Vyberte si knihu v katalogu nebo změňte filtr stavu.</p></div><?php else: ?>
<p class="section" role="status">Počet rezervací: <?= $total ?></p>
<div class="card reservation-table"><div class="table-wrapper"><table class="table"><caption class="catalog-sr">Rezervace, jejich stav a dostupné akce</caption><thead><tr><th scope="col">Kniha</th><?php if ($scope === 'all'): ?><th scope="col">Čtenář</th><?php endif; ?><th scope="col">Datum</th><th scope="col">Stav</th><th scope="col">Akce</th></tr></thead><tbody>
<?php foreach ($reservations as $reservation): ?><tr><td data-label="Kniha"><a href="book.php?book_id=<?= (int)$reservation['book_id'] ?>"><?= h($reservation['title']) ?></a></td><?php if ($scope === 'all'): ?><td data-label="Čtenář"><?= h($reservation['login']) ?></td><?php endif; ?><td data-label="Datum"><?= h(date('d. m. Y H:i', strtotime($reservation['created_at']))) ?></td><td data-label="Stav"><span class="badge <?= $reservation['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>"><?= h($labels[$reservation['status']] ?? $reservation['status']) ?></span></td><td data-label="Akce"><div class="actions">
<?php if ($reservation['status'] === 'active'): ?>
<?php foreach (['cancel','complete'] as $action): if (($action === 'cancel' && (int)$reservation['user_id'] !== (int)$user['id']) || ($action === 'complete' && !$isAdmin)) continue; ?>
<form method="post" action="reservations.php?<?= h($query) ?>"><input type="hidden" name="csrf" value="<?= h($_SESSION['catalog_csrf']) ?>"><input type="hidden" name="book_id" value="<?= (int)$reservation['book_id'] ?>"><input type="hidden" name="reservation_id" value="<?= (int)$reservation['id'] ?>"><button class="btn <?= $action === 'cancel' ? 'btn-danger' : 'btn-success' ?> btn-small" name="action" value="<?= $action ?>"><?= $action === 'cancel' ? 'Zrušit' : 'Dokončit a vrátit kus' ?></button></form>
<?php endforeach; ?>
<?php else: ?><span class="muted">Uzavřená rezervace</span><?php endif; ?>
</div></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php if ($pages > 1): ?><nav class="catalog-pagination" aria-label="Stránkování rezervací"><?php if ($page > 1): ?><a class="btn btn-secondary" href="reservations.php?<?= h($query . '&page=' . ($page - 1)) ?>">← Předchozí</a><?php endif; ?><span>Strana <?= $page ?> z <?= $pages ?></span><?php if ($page < $pages): ?><a class="btn btn-secondary" href="reservations.php?<?= h($query . '&page=' . ($page + 1)) ?>">Další →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; endif; catalog_footer(); ?>
