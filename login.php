<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Если пользователь уже вошёл — отправляем в профиль
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

$error = '';
$msg = $_GET['msg'] ?? '';

if ($msg === 'timeout') {
    $error = "Byli jste odhlášeni z důvodu neaktivity (20 minut).";
} elseif ($msg === 'security') {
    $error = "Relace byla ukončena z bezpečnostních důvodů. Přihlaste se prosím znovu.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($login) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        // Проверка пароля через password_verify()
        if ($user && password_verify($password, $user['password'])) {
            // Регенерация ID сессии (защита от Session Fixation)
            session_regenerate_id(true);

            $_SESSION['user_id']       = $user['id'];
            $_SESSION['login']         = $user['login'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['first_name']    = $user['first_name'];
            $_SESSION['USER_AGENT']    = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['LAST_ACTIVITY'] = time();

            header("Location: profile.php");
            exit();
        } else {
            $error = "Nesprávné přihlašovací jméno nebo heslo.";
        }
    } else {
        $error = "Vyplňte všechna pole.";
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení | Knihovní systém</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { max-width: 400px; margin: 40px auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; background: #fff; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #218838; }
        .alert-error { background: #f8d7da; color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Přihlášení do systému</h2>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="login">Login (uživatelské jméno)</label>
            <input type="text" id="login" name="login" required>
        </div>

        <div class="form-group">
            <label for="password">Heslo</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn-submit">Přihlásit se</button>
    </form>

    <p style="text-align: center; margin-top: 15px;">
        Nemáte účet? <a href="register.php">Zaregistrujte se</a>
    </p>
</div>
</body>
</html>