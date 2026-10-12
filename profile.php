<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Доступ только для авторизованных
require_login();

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Můj profil | Knihovní systém</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container { max-width: 500px; margin: 40px auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; }
        .row { margin-bottom: 12px; }
        .label { font-weight: bold; color: #555; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #dc3545; color: white; }
        .badge-user { background: #007bff; color: white; }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>Můj profil</h2>
        <div class="row"><span class="label">Uživatelské jméno:</span> <?= htmlspecialchars($user['login']) ?></div>
        <div class="row"><span class="label">Jméno a příjmení:</span> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <div class="row"><span class="label">E-mail:</span> <?= htmlspecialchars($user['email']) ?></div>
        <div class="row"><span class="label">Telefon:</span> <?= htmlspecialchars($user['phone']) ?></div>
        <div class="row"><span class="label">Pohlaví:</span> <?= htmlspecialchars($user['gender']) ?></div>
        <div class="row">
            <span class="label">Role v systému:</span>
            <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                <?= htmlspecialchars(strtoupper($user['role'])) ?>
            </span>
        </div>
        <hr style="margin: 20px 0;">
        <p><a href="logout.php" style="color: #dc3545;">Odhlásit se</a></p>
    </div>
</body>
</html>