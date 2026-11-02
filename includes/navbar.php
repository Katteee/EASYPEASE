<?php
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0');
    $unreadStmt->execute([(int)$_SESSION['user_id']]);
    $unreadCount = (int)$unreadStmt->fetchColumn();
}
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $roleStmt = $pdo->prepare('SELECT role FROM users WHERE id=?');
    $roleStmt->execute([(int)$_SESSION['user_id']]);
    $isAdmin = $roleStmt->fetchColumn() === 'admin';
}
?>
<nav class="navbar">
    <div class="navbar-container">

        <a href="../index.php" class="navbar-brand">
            📚 Knihovna
        </a>

        <ul class="navbar-menu">
            <li><a href="../books.php">Knihy</a></li>
            <li><a href="../reservations.php">Rezervace</a></li>
            <li><a href="../messages/inbox.php">Zprávy<?= $unreadCount ? " (".$unreadCount.")" : "" ?></a></li>
            <?php if ($isAdmin): ?><li><a href="../admin/books.php">Administrace</a></li><li><a href="../admin/users.php">Uživatelé</a></li>
            <li><a href="../admin/dashboard.php">Dashboard</a></li><?php endif; ?>
            <li><a href="../profile.php">Profil</a></li>
            <li><a href="../logout.php">Odhlásit</a></li>
        </ul>

    </div>
</nav>