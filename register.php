<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Если пользователь уже вошёл — отправляем в профиль
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

$errors = [];
$success = '';

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
        $errors[] = 'Vyplňte všechna povinná pole.';
    }

    if (strlen($login) < 3) {
        $errors[] = 'Login musí mít alespoň 3 znaky.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Heslo musí mít alespoň 6 znaků.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Zadejte platný formát e-mailu.';
    }

    if (!preg_match('/^[0-9+\s-]{9,15}$/', $phone)) {
        $errors[] = 'Zadejte platné telefonní číslo.';
    }

    // Проверка уникальности логина и email через Prepared Statements
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ? OR email = ?");
        $stmt->execute([$login, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Uživatel s tímto přihlašovacím jménem nebo e-mailem již existuje.';
        }
    }

    // Сохранение в БД
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (login, password, first_name, last_name, email, phone, gender, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'user')
        ");

        if ($insert->execute([$login, $passwordHash, $first_name, $last_name, $email, $phone, $gender])) {
            $success = 'Registrace byla úspěšná! Nyní se můžete <a href="login.php">přihlásit</a>.';
        } else {
            $errors[] = 'Chyba při ukládání dat do databáze.';
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
        .form-container { max-width: 450px; margin: 40px auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background-color: #0056b3; }
        .error-box { background: #ffebee; color: #c62828; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success-box { background: #e8f5e9; color: #2e7d32; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Registrace nového čtenáře</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="login">Login (uživatelské jméno)*</label>
                <input type="text" id="login" name="login" required minlength="3" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Heslo*</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="first_name">Jméno*</label>
                <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Příjmení*</label>
                <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">E-mail*</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="phone">Telefon*</label>
                <input type="tel" id="phone" name="phone" placeholder="+420123456789" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="gender">Pohlaví*</label>
                <select id="gender" name="gender" required>
                    <option value="">-- Vyberte pohlaví --</option>
                    <option value="male" <?= (($_POST['gender'] ?? '') === 'male') ? 'selected' : '' ?>>Muž</option>
                    <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Žena</option>
                    <option value="other" <?= (($_POST['gender'] ?? '') === 'other') ? 'selected' : '' ?>>Jiné</option>
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