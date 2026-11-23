<?php
require_once __DIR__ . '/common.php';
$userId = (int)$_SESSION['user_id'];
$messages = [];
$error = '';
$notice = $_SESSION['message_notice'] ?? '';
unset($_SESSION['message_notice']);
try {
    $stmt = $pdo->prepare('SELECT m.id, m.subject, m.is_read, m.created_at, u.first_name, u.last_name FROM messages m JOIN users u ON u.id = m.receiver_id WHERE m.sender_id = ? ORDER BY m.created_at DESC, m.id DESC');
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log((string)$ex);
    $error = 'Odeslané zprávy se nepodařilo načíst.';
}
msg_header('Odeslané zprávy', 'sent');
if ($notice): ?><div class="alert alert-success" role="status"><?= msg_h($notice) ?></div><?php endif;
if ($error): ?><div class="alert alert-error" role="alert"><?= msg_h($error) ?></div><?php endif; ?>
<div class="card"><h2>Odeslané zprávy</h2>
<?php if (!$messages && !$error): ?><p>Zatím nemáte žádné odeslané zprávy.</p><?php endif; ?>
<?php foreach ($messages as $message): ?>
<a class="message-item" href="detail.php?id=<?= (int)$message['id'] ?>">
<div><strong>Komu: <?= msg_h($message['first_name'] . ' ' . $message['last_name']) ?></strong>
<p><?= msg_h($message['subject']) ?></p><span class="badge <?= $message['is_read'] ? 'badge-success' : 'badge-warning' ?>"><?= $message['is_read'] ? 'Přečteno' : 'Nepřečteno' ?></span></div>
<span class="message-date"><?= msg_h(msg_date($message['created_at'])) ?></span></a>
<?php endforeach; ?></div>
<?php msg_footer(); ?>
