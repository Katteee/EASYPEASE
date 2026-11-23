<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Если пользователь уже авторизован — перенаправляем в профиль
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

$errors = [];
$success = '';
$login = $first_name = $last_name = $email = $phone = $gender = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login      = trim($_POST['login'] ?? '');
    $password   = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $gender     = $_POST['gender'] ?? '';

    // Серверная валидация полей
    if (empty($login) || empty($password) || empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($gender)) {
        $errors[] = "Vyplňte všechna povinná pole.";
    }
    if (strlen($login) < 3 || strlen($login) > 50) {
        $errors[] = "Login musí mít 3 až 50 znaků.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Heslo musí mít alespoň 6 znaků.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Zadejte platný formát e-mailu.";
    }
    if (!preg_match('/^[0-9+\s\-]{9,20}$/', $phone)) {
        $errors[] = "Zadejte platné telefonní číslo (např. +420123456789).";
    }
    if (!in_array($gender, ['male', 'female', 'other'], true)) {
        $errors[] = "Vyberte platné pohlaví.";
    }

    // Проверка уникальности логина и email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ? OR email = ?");
        $stmt->execute([$login, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Uživatel s tímto přihlašovacím jménem nebo e-mailem již existuje.";
        }
    }

    // Хэширование пароля и запись в БД
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (login, password, first_name, last_name, email, phone, gender, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'user')
        ");

        if ($insert->execute([$login, $passwordHash, $first_name, $last_name, $email, $phone, $gender])) {
            $success = "Registrace byla úspěšná! Nyní se můžete přihlásit.";
            $login = $first_name = $last_name = $email = $phone = $gender = '';
        } else {
            $errors[] = "Nastala chyba při ukládání do databáze. Zkuste to prosím znovu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace | Knihovní systém</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { max-width: 450px; margin: 40px auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; background: #fff; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { width: 100%; padding: 10px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #0056b3; }
        .alert-error { background: #f8d7da; color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d1e7dd; color: #0f5132; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Registrace nového čtenáře</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success">
            <?= htmlspecialchars($success) ?> <a href="login.php">Přejít na přihlášení</a>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="login">Login (uživatelské jméno)*</label>
            <input type="text" id="login" name="login" value="<?= htmlspecialchars($login) ?>" minlength="3" maxlength="50" required>
        </div>

        <div class="form-group">
            <label for="password">Heslo*</label>
            <input type="password" id="password" name="password" minlength="6" required>
        </div>

        <div class="form-group">
            <label for="first_name">Jméno*</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Příjmení*</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail*</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Telefon*</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="+420123456789" pattern="[0-9+\s\-]{9,20}" required>
        </div>

        <div class="form-group">
            <label for="gender">Pohlaví*</label>
            <select id="gender" name="gender" required>
                <option value="">-- Vyberte pohlaví --</option>
                <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Muž</option>
                <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Žena</option>
                <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Jiné</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">Zaregistrovat se</button>
    </form>

    <p style="text-align: center; margin-top: 15px;">
        Již máte účet? <a href="login.php">Přihlaste se</a>
    </p>
</div>
</body>
</html>