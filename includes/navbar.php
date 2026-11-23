
<?php
// Session and database initialization.
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = false;
$unreadCount = 0;

// Resolve links for root and nested PHP pages.
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$prefix = preg_match(
    '~/(admin|messages)/[^/]+$~',
    $scriptPath
) ? '../' : './';

if ($isLoggedIn) {
    $userId = (int) $_SESSION['user_id'];

    // Always check the current role in the database.
    $stmt = $pdo->prepare(
        "SELECT role FROM users WHERE id = ?"
    );
    $stmt->execute([$userId]);
    $isAdmin = $stmt->fetchColumn() === 'admin';

    // Count unread messages for the current user.
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM messages
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $unreadCount = (int) $stmt->fetchColumn();
}
?>

<nav class="navbar">
    <div class="navbar-container">

        <a href="<?= $prefix ?>books.php"
           class="navbar-brand">
            📚 Knihovna
        </a>

        <ul class="navbar-menu">

            <li>
                <a href="<?= $prefix ?>books.php">
                    Knihy
                </a>
            </li>

            <?php if ($isLoggedIn): ?>

                <li>
                    <a href="<?= $prefix ?>reservations.php">
                        Moje rezervace
                    </a>
                </li>

                <li>
                    <a href="<?= $prefix ?>messages/inbox.php">
                        Zprávy
                        <?php if ($unreadCount > 0): ?>
                            <span class="nav-counter">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <li>
                        <a href="<?= $prefix ?>admin/dashboard.php">
                            Dashboard
                        </a>
                    </li>

                    <li>
                        <a href="<?= $prefix ?>admin/books.php">
                            Administrace knih
                        </a>
                    </li>

                    <li>
                        <a href="<?= $prefix ?>admin/users.php">
                            Uživatelé
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="<?= $prefix ?>profile.php">
                        Profil
                    </a>
                </li>

                <li>
                    <a href="<?= $prefix ?>logout.php">
                        Odhlásit
                    </a>
                </li>

            <?php else: ?>

                <li>
                    <a href="<?= $prefix ?>login.php">
                        Přihlášení
                    </a>
                </li>

                <li>
                    <a href="<?= $prefix ?>register.php">
                        Registrace
                    </a>
                </li>

            <?php endif; ?>

        </ul>
    </div>
</nav>
