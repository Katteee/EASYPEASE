<?php
require __DIR__ . '/includes/catalog.php';
require_login();
require __DIR__ . '/includes/reservation_service.php';
$userId = (int)$_SESSION['user_id'];
$error = '';
$listError = '';
$notice = $_SESSION['reservation_notice'] ?? '';
unset($_SESSION['reservation_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? null;
    $id = filter_var($_POST['book_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['action'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['catalog_csrf'], $token)) {
        http_response_code(403);
        $error = 'Neplatný formulář. Obnovte stránku.';
    } elseif ($id === false || !is_string($action) || !in_array($action, ['reserve', 'cancel'], true)) {
        http_response_code(400);
        $error = 'Neplatné ID knihy nebo akce.';
    } else {
        try {
            change_reservation($pdo, $userId, $id, $action);
            $_SESSION['reservation_notice'] = $action === 'reserve' ? 'Kniha byla rezervována.' : 'Rezervace byla zrušena.';
            header('Location: reservations.php', true, 303);
            exit;
        } catch (DomainException $e) {
            http_response_code(409);
            $error = $e->getMessage();
        } catch (Throwable $e) {
            error_log((string)$e);
            http_response_code(503);
            $error = 'Rezervaci se nepodařilo uložit. Zkuste to znovu.';
        }
    }
}
$reservations = [];
try {
    $stmt = $pdo->prepare('SELECT r.id, r.book_id, r.status, r.created_at, b.title FROM reservations r JOIN books b ON b.id = r.book_id WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC');
    $stmt->execute([$userId]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log((string)$e);
    http_response_code(503);
    $listError = 'Rezervace se nepodařilo načíst.';
}
$labels = ['active' => 'Aktivní', 'cancelled' => 'Zrušená', 'completed' => 'Dokončená'];
catalog_header('Moje rezervace');
?>
<?php if ($notice): ?><p class="alert alert-success" role="status"><?= h($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="alert alert-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if ($listError): ?><p class="alert alert-error" role="alert"><?= h($listError) ?></p>
<?php elseif (!$reservations): ?><div class="card"><h2>Zatím nemáte žádné rezervace</h2><p class="section">Vyberte si knihu v katalogu.</p></div>
<?php else: ?>
<div class="card table-wrapper"><table class="table"><thead><tr><th>Kniha</th><th>Datum</th><th>Stav</th><th>Akce</th></tr></thead><tbody>
<?php foreach ($reservations as $reservation): ?>
<tr><td><a href="book.php?book_id=<?= (int)$reservation['book_id'] ?>"><?= h($reservation['title']) ?></a></td><td><?= h(date('d. m. Y H:i', strtotime($reservation['created_at']))) ?></td><td><span class="badge <?= $reservation['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>"><?= h($labels[$reservation['status']] ?? $reservation['status']) ?></span></td><td>
<?php if ($reservation['status'] === 'active'): ?><form method="post" action="reservations.php"><input type="hidden" name="csrf" value="<?= h($_SESSION['catalog_csrf']) ?>"><input type="hidden" name="book_id" value="<?= (int)$reservation['book_id'] ?>"><button class="btn btn-danger btn-small" name="action" value="cancel">Zrušit</button></form><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?><a class="btn btn-primary" href="books.php">Procházet katalog</a>
<?php catalog_footer(); ?>
