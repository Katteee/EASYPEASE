<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_login();

function msg_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function msg_date($value): string {
    return date('d. m. Y H:i', strtotime((string)$value));
}
function msg_header(string $title, string $active = ''): void {
    ?>
<!DOCTYPE html>
<html lang="cs"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= msg_h($title) ?> | Knihovna</title><link rel="stylesheet" href="../css/style.css"></head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main><div class="container"><h1 class="page-title"><?= msg_h($title) ?></h1>
<nav class="message-navigation" aria-label="Navigace zpráv">
<a class="btn <?= $active === 'inbox' ? 'btn-primary' : 'btn-secondary' ?>" href="inbox.php">Přijaté zprávy</a>
<a class="btn <?= $active === 'sent' ? 'btn-primary' : 'btn-secondary' ?>" href="sent.php">Odeslané zprávy</a>
<a class="btn <?= $active === 'new' ? 'btn-primary' : 'btn-success' ?>" href="new.php">+ Nová zpráva</a>
</nav>
<?php
}
function msg_footer(): void { echo '</div></main></body></html>'; }
