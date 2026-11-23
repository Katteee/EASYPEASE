<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Доступ только для авторизованных пользователей
require_login();

// ID берётся строго из сессии (защита от подмены чужого ID)
$userId  = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Обновление личных данных и пароля
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $gender     = $_POST['gender'] ?? '';
        $new_pass   = $_POST['new_password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || !in_array($gender, ['male', 'female', 'other'], true)) {
            $errors[] = "Vyplňte prosím všechna povinná pole.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Zadejte platný formát e-mailu.";
        }
        if (!preg_match('/^[0-9+\s\-]{9,20}$/', $phone)) {
            $errors[] = "Zadejte platné telefonní číslo.";
        }
        if (!empty($new_pass) && strlen($new_pass) < 6) {
            $errors[] = "Nové heslo musí mít alespoň 6 znaků.";
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $userId]);
            if ($check->fetch()) {
                $errors[] = "Tento e-mail již používá jiný uživatel.";
            }
        }

        if (empty($errors)) {
            if (!empty($new_pass)) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd  = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, gender = ?, password = ? WHERE id = ?");
                $upd->execute([$first_name, $last_name, $email, $phone, $gender, $hash, $userId]);
            } else {
                $upd = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, gender = ? WHERE id = ?");
                $upd->execute([$first_name, $last_name, $email, $phone, $gender, $userId]);
            }

            $_SESSION['first_name'] = $first_name;
            $success = "Údaje byly úspěšně aktualizovány.";

            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }

    // 2. Загрузка аватара, ресайз 800 x XXX и конвертация в JPEG (quality 90)
    if ($action === 'upload_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Chyba při nahrávání souboru. Vyberte prosím obrázek.";
        } else {
            $fileTmp  = $_FILES['avatar']['tmp_name'];
            $fileSize = $_FILES['avatar']['size'];

            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = "Soubor je příliš velký (maximum je 5 MB).";
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($fileTmp);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($mime, $allowedMimes, true)) {
                $errors[] = "Nepodporovaný formát. Povoleny jsou pouze JPEG, PNG, GIF a WebP.";
            }

            $imgInfo = @getimagesize($fileTmp);
            if ($imgInfo === false || $imgInfo[0] < 50 || $imgInfo[1] < 50) {
                $errors[] = "Soubor není platný obrázek nebo má příliš malé rozlišení (min. 50×50 px).";
            }

            if (empty($errors)) {
                if (!extension_loaded('gd')) {
                    $errors[] = "Na serveru není povoleno rozšíření PHP GD pro zpracování obrázků.";
                } else {
                    $srcImg = match ($mime) {
                        'image/jpeg' => @imagecreatefromjpeg($fileTmp),
                        'image/png'  => @imagecreatefrompng($fileTmp),
                        'image/gif'  => @imagecreatefromgif($fileTmp),
                        'image/webp' => @imagecreatefromwebp($fileTmp),
                        default      => false,
                    };

                    if (!$srcImg) {
                        $errors[] = "Obrázek se nepodařilo zpracovat.";
                    } else {
                        $origW = imagesx($srcImg);
                        $origH = imagesy($srcImg);

                        // Пропорциональный ресайз до ширины 800 px (800 x XXX)
                        $targetW = 800;
                        $targetH = (int)round($origH * ($targetW / $origW));

                        $dstImg = imagecreatetruecolor($targetW, $targetH);
                        $white  = imagecolorallocate($dstImg, 255, 255, 255);
                        imagefill($dstImg, 0, 0, $white);
                        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

                        $uploadDir = __DIR__ . '/uploads/avatars/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        $newFileName = 'avatar_' . $userId . '_' . time() . '.jpg';
                        $targetPath  = $uploadDir . $newFileName;

                        // Сохранение в JPEG с качеством 90
                        if (imagejpeg($dstImg, $targetPath, 90)) {
                            $oldAvatar = $user['avatar'] ?? '';
                            if ($oldAvatar && $oldAvatar !== 'default_avatar.jpg' && file_exists($uploadDir . $oldAvatar)) {
                                @unlink($uploadDir . $oldAvatar);
                            }

                            $updAvatar = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                            $updAvatar->execute([$newFileName, $userId]);

                            $success = "Profilová fotografie byla úspěšně nahrána (JPEG 800×{$targetH}, kvalita 90).";
                            $stmt->execute([$userId]);
                            $user = $stmt->fetch();
                        } else {
                            $errors[] = "Nepodařilo se uložit zpracovaný obrázek na server.";
                        }

                        imagedestroy($srcImg);
                        imagedestroy($dstImg);
                    }
                }
            }
        }
    }
}

$avatarFile      = $user['avatar'] ?? 'default_avatar.jpg';
$avatarPath      = 'uploads/avatars/' . $avatarFile;
$hasCustomAvatar = ($avatarFile !== 'default_avatar.jpg' && file_exists(__DIR__ . '/' . $avatarPath));
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Můj profil | Knihovní systém</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container { max-width: 650px; margin: 40px auto; padding: 25px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; background: #fff; }
        .avatar-box { text-align: center; margin-bottom: 20px; }
        .avatar-img { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #007bff; }
        .avatar-placeholder { width: 150px; height: 150px; border-radius: 50%; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center; font-size: 48px; color: #6c757d; border: 3px solid #ddd; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-primary:hover { background: #0056b3; }
        .alert-error { background: #f8d7da; color: #842029; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d1e7dd; color: #0f5132; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-admin { background: #dc3545; color: #fff; }
        .badge-user { background: #007bff; color: #fff; }
        hr { margin: 25px 0; border: 0; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="profile-container">
    <h2>Můj profil (<?= htmlspecialchars($user['login']) ?>)
        <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
            <?= htmlspecialchars(strtoupper($user['role'])) ?>
        </span>
    </h2>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="avatar-box">
        <?php if ($hasCustomAvatar): ?>
            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Profilová fotografie" class="avatar-img">
        <?php else: ?>
            <div class="avatar-placeholder">👤</div>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_avatar">
        <div class="form-group">
            <label for="avatar">Změnit profilovou fotografii (JPEG, PNG, GIF, WebP — max 5 MB):</label>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
        </div>
        <button type="submit" class="btn btn-primary">Nahrát fotografii</button>
    </form>

    <hr>

    <h3>Upravit osobní údaje</h3>
    <form method="POST">
        <input type="hidden" name="action" value="update_profile">

        <div class="form-group">
            <label for="first_name">Jméno*</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Příjmení*</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail*</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Telefon*</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>

        <div class="form-group">
            <label for="gender">Pohlaví*</label>
            <select id="gender" name="gender" required>
                <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Muž</option>
                <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Žena</option>
                <option value="other" <?= $user['gender'] === 'other' ? 'selected' : '' ?>>Jiné</option>
            </select>
        </div>

        <div class="form-group">
            <label for="new_password">Nové heslo (ponechte prázdné, pokud nechcete měnit)</label>
            <input type="password" id="new_password" name="new_password" minlength="6" placeholder="Min. 6 znaků">
        </div>

        <button type="submit" class="btn btn-primary">Uložit změny</button>
    </form>

    <hr>
    <p><a href="logout.php" style="color: #dc3545; font-weight: bold;">Odhlásit se</a></p>
</div>
</body>
</html>