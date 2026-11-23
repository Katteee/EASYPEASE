<?php
require_once __DIR__ . '/../includes/admin_guard.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
if (!$id) { http_response_code(400); exit('Neplatné ID.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_csrf();
    $first = trim((string)($_POST['first_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = (string)($_POST['role'] ?? '');
    if ($first === '' || $last === '' || mb_strlen($first)>50 || mb_strlen($last)>50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>100 || !in_array($role, ['user','admin'], true)) {
        $error = 'Zkontrolujte zadané údaje.';
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT role FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $oldRole = $lock->fetchColumn();
            if ($oldRole === false) throw new RuntimeException('Uživatel neexistuje.');
            if ($oldRole === 'admin' && $role === 'user') {
                $admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
                if ($admins <= 1) throw new RuntimeException('Nelze odebrat posledního administrátora.');
            }
            $upd = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?');
            $upd->execute([$first,$last,$email,$role,$id]);
            $pdo->commit();
            if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') { header('Location: ../profile.php', true, 303); exit; }
            header('Location: user_detail.php?id='.$id.'&saved=1', true, 303); exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $e instanceof PDOException ? 'Údaje nelze uložit (možná duplicitní e-mail).' : $e->getMessage();
        }
    }
}
$stmt = $pdo->prepare('SELECT id, login, first_name, last_name, email, phone, gender, role, created_at FROM users WHERE id=?');
$stmt->execute([$id]); $u = $stmt->fetch();
if (!$u) { http_response_code(404); exit('Uživatel nenalezen.'); }
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><title>Detail uživatele</title><link rel="stylesheet" href="../css/style.css"></head><body><?php include __DIR__.'/../includes/navbar.php'; ?>
<main class="container"><h1 class="page-title">Detail uživatele #<?= (int)$u['id'] ?></h1><div class="card form-container">
<?php if ($error): ?><div class="alert alert-error"><?= admin_h($error) ?></div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Změny byly uloženy.</div><?php endif; ?>
<p>Login: <?= admin_h($u['login']) ?> | Telefon: <?= admin_h($u['phone']) ?> | Registrace: <?= admin_h($u['created_at']) ?></p>
<form method="post"><input type="hidden" name="csrf" value="<?= admin_h(admin_csrf()) ?>">
<?php foreach (['first_name'=>'Jméno','last_name'=>'Příjmení','email'=>'E-mail'] as $field=>$label): ?><div class="form-group"><label for="<?= $field ?>"><?= $label ?></label><input class="form-control" id="<?= $field ?>" name="<?= $field ?>" value="<?= admin_h($u[$field]) ?>" required></div><?php endforeach; ?>
<div class="form-group"><label for="role">Role</label><select class="form-control" id="role" name="role"><option value="user" <?= $u['role']==='user'?'selected':'' ?>>user</option><option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option></select></div>
<button class="btn btn-primary">Uložit změny</button> <a class="btn btn-secondary" href="users.php">Zpět</a></form></div></main></body></html>
