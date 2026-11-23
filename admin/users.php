<?php
require_once __DIR__ . '/../includes/admin_guard.php';
$users = $pdo->query('SELECT id, login, first_name, last_name, email, role, created_at FROM users ORDER BY id DESC')->fetchAll();
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><title>Správa uživatelů</title><link rel="stylesheet" href="../css/style.css"></head><body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<main class="container"><h1 class="page-title">Správa uživatelů</h1><div class="card"><div class="table-wrapper"><table class="table"><thead><tr><th>ID</th><th>Jméno</th><th>Login</th><th>E-mail</th><th>Role</th><th>Akce</th></tr></thead><tbody>
<?php foreach ($users as $u): ?><tr><td><?= (int)$u['id'] ?></td><td><?= admin_h($u['first_name'].' '.$u['last_name']) ?></td><td><?= admin_h($u['login']) ?></td><td><?= admin_h($u['email']) ?></td><td><?= admin_h($u['role']) ?></td><td><a class="btn btn-primary" href="user_detail.php?id=<?= (int)$u['id'] ?>">Detail / Upravit</a></td></tr><?php endforeach; ?>
</tbody></table></div></div></main></body></html>
