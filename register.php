<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Якщо користувач уже увійшов
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

// CSRF-захист
if (empty($_SESSION['register_csrf'])) {
    $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function textLength($value) {
    return mb_strlen($value, 'UTF-8');
}

$errors = [];
$success = '';

$login = '';
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$gender = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim((string)($_POST['login'] ?? ''));
    $password = $_POST['password'] ?? '';
    $first_name = trim((string)($_POST['first_name'] ?? ''));
    $last_name = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $gender = $_POST['gender'] ?? '';

    // 1. CSRF
    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['register_csrf'], $token)) {
        $errors[] = 'Neplatný formulář. Obnovte stránku.';
    }

    // 2. Перевірка обов'язкових полів
    if (
        $login === '' ||
        !is_string($password) ||
        $password === '' ||
        $first_name === '' ||
        $last_name === '' ||
        $email === '' ||
        $phone === '' ||
        $gender === ''
    ) {
        $errors[] = 'Vyplňte všechna povinná pole.';
    }

    // 3. Login
    if (
        textLength($login) < 3 ||
        textLength($login) > 50
    ) {
        $errors[] = 'Login musí mít 3 až 50 znaků.';
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
        $errors[] = 'Login může obsahovat pouze písmena bez diakritiky, čísla a podtržítko.';
    }

    // 4. Heslo
    if (is_string($password)) {
        if (strlen($password) < 8) {
            $errors[] = 'Heslo musí mít alespoň 8 znaků.';
        }

        if (strlen($password) > 72) {
            $errors[] = 'Heslo je příliš dlouhé (maximum 72 bajtů).';
        }
    }

    // 5. Jméno
    if (
        textLength($first_name) < 2 ||
        textLength($first_name) > 50
    ) {
        $errors[] = 'Jméno musí mít 2 až 50 znaků.';
    }

    // 6. Příjmení
    if (
        textLength($last_name) < 2 ||
        textLength($last_name) > 50
    ) {
        $errors[] = 'Příjmení musí mít 2 až 50 znaků.';
    }

    // 7. E-mail
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 100
    ) {
        $errors[] = 'Zadejte platnou e-mailovou adresu (max. 100 znaků).';
    }

    // 8. Telefon
    if (
        textLength($phone) > 20 ||
        !preg_match('/^\+?[0-9][0-9\s-]{7,18}$/', $phone)
    ) {
        $errors[] = 'Zadejte platné telefonní číslo.';
    }

    // 9. Pohlaví
    if (!in_array($gender, ['male', 'female', 'other'], true)) {
        $errors[] = 'Vyberte platné pohlaví.';
    }

    // 10. Kontrola unikátnosti loginu a e-mailu
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM users
                 WHERE login = ? OR email = ?
                 LIMIT 1"
            );

            $stmt->execute([$login, $email]);

            if ($stmt->fetch()) {
                $errors[] = 'Uživatel s tímto loginem nebo e-mailem již existuje.';
            }
        } catch (PDOException $e) {
            error_log((string)$e);
            $errors[] = 'Chyba při kontrole uživatele.';
        }
    }

    // 11. Uložení do databáze
    if (empty($errors)) {
        try {
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (login, password, first_name, last_name,
                 email, phone, gender, role)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'user')"
            );

            $stmt->execute([
                $login,
                $passwordHash,
                $first_name,
                $last_name,
                $email,
                $phone,
                $gender
            ]);

            $success = 'Registrace byla úspěšná! Nyní se můžete přihlásit.';

            // Новий CSRF-токен
            $_SESSION['register_csrf'] = bin2hex(random_bytes(32));

            // Очистити formulář
            $login = '';
            $first_name = '';
            $last_name = '';
            $email = '';
            $phone = '';
            $gender = '';

        } catch (PDOException $e) {
            error_log((string)$e);

            if ($e->getCode() === '23000') {
                $errors[] = 'Login nebo e-mail již existuje.';
            } else {
                $errors[] = 'Registraci se nepodařilo uložit. Zkuste to později.';
            }
        } catch (Throwable $e) {
            error_log((string)$e);
            $errors[] = 'Při registraci došlo k chybě.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Registrace | Knihovní systém</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .form-container {
            max-width: 450px;
            margin: 40px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-submit:hover {
            background-color: #0056b3;
        }

        .error-box {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .success-box {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .form-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #007bff;
        }
    </style>
</head>

<body>

<div class="form-container">

    <h2>Registrace nového čtenáře</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-box" role="alert">
            <?php foreach ($errors as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-box" role="status">
            <?= e($success) ?>
            <a href="login.php">Přihlásit se</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">

        <input
            type="hidden"
            name="csrf"
            value="<?= e($_SESSION['register_csrf']) ?>"
        >

        <div class="form-group">
            <label for="login">Login (uživatelské jméno)*</label>

            <input
                type="text"
                id="login"
                name="login"
                required
                minlength="3"
                maxlength="50"
                pattern="[a-zA-Z0-9_]+"
                autocomplete="username"
                value="<?= e($login) ?>"
            >
        </div>

        <div class="form-group">
            <label for="password">Heslo*</label>

            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                autocomplete="new-password"
            >
        </div>

        <div class="form-group">
            <label for="first_name">Jméno*</label>

            <input
                type="text"
                id="first_name"
                name="first_name"
                required
                minlength="2"
                maxlength="50"
                value="<?= e($first_name) ?>"
            >
        </div>

        <div class="form-group">
            <label for="last_name">Příjmení*</label>

            <input
                type="text"
                id="last_name"
                name="last_name"
                required
                minlength="2"
                maxlength="50"
                value="<?= e($last_name) ?>"
            >
        </div>

        <div class="form-group">
            <label for="email">E-mail*</label>

            <input
                type="email"
                id="email"
                name="email"
                required
                maxlength="100"
                autocomplete="email"
                value="<?= e($email) ?>"
            >
        </div>

        <div class="form-group">
            <label for="phone">Telefon*</label>

            <input
                type="tel"
                id="phone"
                name="phone"
                required
                maxlength="20"
                placeholder="+420123456789"
                autocomplete="tel"
                value="<?= e($phone) ?>"
            >
        </div>

        <div class="form-group">
            <label for="gender">Pohlaví*</label>

            <select
                id="gender"
                name="gender"
                required
            >
                <option value="">-- Vyberte pohlaví --</option>

                <option value="male"
                    <?= $gender === 'male' ? 'selected' : '' ?>>
                    Muž
                </option>

                <option value="female"
                    <?= $gender === 'female' ? 'selected' : '' ?>>
                    Žena
                </option>

                <option value="other"
                    <?= $gender === 'other' ? 'selected' : '' ?>>
                    Jiné
                </option>
            </select>
        </div>

        <button type="submit" class="btn-submit">
            Zaregistrovat se
        </button>

    </form>

    <p class="login-link">
        Již máte účet?
        <a href="login.php">Přihlaste se</a>
    </p>

</div>

</body>
</html>
