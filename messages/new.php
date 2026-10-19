<?php
require_once __DIR__ . '/common.php';
$_SESSION['messages_csrf'] ??= bin2hex(random_bytes(32));
$userId = (int)$_SESSION['user_id'];
$errors = [];
$recipient = '';
$subject = '';
$body = '';
$users = [];
try {
    $stmt = $pdo->prepare('SELECT id, login, first_name, last_name FROM users WHERE id <> ? ORDER BY last_name, first_name, login');
    $stmt->execute([$userId]);
    $users = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log((string)$ex);
    $errors[] = 'Seznam příjemců se nepodařilo načíst.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? null;
    $recipient = is_string($_POST['recipient'] ?? null) ? $_POST['recipient'] : '';
    $subject = is_string($_POST['subject'] ?? null) ? trim($_POST['subject']) : '';
    $body = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
    $receiverId = filter_var($recipient, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!is_string($token) || !hash_equals($_SESSION['messages_csrf'], $token)) {
        http_response_code(403);
        $errors[] = 'Neplatný bezpečnostní token.';
    }
    if ($receiverId === false || $receiverId === null || (int)$receiverId === $userId) $errors[] = 'Vyberte platného příjemce.';
    if ($subject === '' || mb_strlen($subject, 'UTF-8') > 150) $errors[] = 'Předmět musí mít 1 až 150 znaků.';
    if ($body === '') $errors[] = 'Napište text zprávy.';
    if (!$users) $errors[] = 'Příjemci nejsou dostupní.';
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND id <> ?');
            $stmt->execute([$receiverId, $userId]);
            if (!$stmt->fetch()) {
                $errors[] = 'Příjemce neexistuje.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $receiverId, $subject, $body]);
                $_SESSION['message_notice'] = 'Zpráva byla úspěšně odeslána.';
                header('Location: sent.php', true, 303);
                exit;
            }
        } catch (PDOException $ex) {
            error_log((string)$ex);
            $errors[] = 'Zprávu se nepodařilo odeslat.';
        }
    }
}
msg_header('Nová zpráva', 'new');
foreach ($errors as $error): ?>
<div class="alert alert-error" role="alert"><?= msg_h($error) ?></div>
<?php endforeach; ?>
<div class="card form-container"><h2>Napsat zprávu</h2>
<form method="post" action="new.php">
<input type="hidden" name="csrf" value="<?= msg_h($_SESSION['messages_csrf']) ?>">
<div class="form-group"><label for="recipient">Příjemce</label>
<select class="form-control" id="recipient" name="recipient" required>
<option value="">Vyberte příjemce</option>
<?php foreach ($users as $user): ?>
<option value="<?= (int)$user['id'] ?>" <?= (string)$user['id'] === $recipient ? 'selected' : '' ?>><?= msg_h($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['login'] . ')') ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label for="subject">Předmět</label><input class="form-control" id="subject" name="subject" maxlength="150" required value="<?= msg_h($subject) ?>"></div>
<div class="form-group"><label for="message">Zpráva</label><textarea class="form-control" id="message" name="message" rows="7" required><?= msg_h($body) ?></textarea></div>
<button class="btn btn-primary" type="submit" <?= !$users ? 'disabled' : '' ?>>Odeslat zprávu</button>
</form></div>
<?php msg_footer(); ?>
