<?php
require_once __DIR__ . '/common.php';
$userId = (int)$_SESSION['user_id'];
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) { http_response_code(400); exit('Neplatné ID zprávy.'); }
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT m.*, s.first_name AS sender_first, s.last_name AS sender_last, r.first_name AS receiver_first, r.last_name AS receiver_last FROM messages m JOIN users s ON s.id = m.sender_id JOIN users r ON r.id = m.receiver_id WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?) FOR UPDATE');
    $stmt->execute([$id, $userId, $userId]);
    $message = $stmt->fetch();
    if ($message && (int)$message['receiver_id'] === $userId && !(int)$message['is_read']) {
        $update = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?');
        $update->execute([$id, $userId]);
        $message['is_read'] = 1;
    }
    $pdo->commit();
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log((string)$ex);
    http_response_code(503);
    exit('Zprávu se nepodařilo načíst.');
}
if (!$message) { http_response_code(404); exit('Zpráva nebyla nalezena.'); }
$received = (int)$message['receiver_id'] === $userId;
msg_header('Detail zprávy', $received ? 'inbox' : 'sent');
?>
<div class="card"><h2><?= msg_h($message['subject']) ?></h2>
<p><strong>Od:</strong> <?= msg_h($message['sender_first'] . ' ' . $message['sender_last']) ?></p>
<p><strong>Komu:</strong> <?= msg_h($message['receiver_first'] . ' ' . $message['receiver_last']) ?></p>
<p><strong>Datum a čas:</strong> <?= msg_h(msg_date($message['created_at'])) ?></p>
<p><strong>Stav:</strong> <span class="badge <?= $message['is_read'] ? 'badge-success' : 'badge-warning' ?>"><?= $message['is_read'] ? 'Přečteno' : 'Nepřečteno' ?></span></p>
<hr style="margin:20px 0">
<p style="white-space:pre-wrap;overflow-wrap:anywhere"><?= msg_h($message['body']) ?></p>
</div><a class="btn btn-secondary" href="<?= $received ? 'inbox.php' : 'sent.php' ?>">← Zpět</a>
<?php msg_footer(); ?>
