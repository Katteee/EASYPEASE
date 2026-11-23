
<?php
// Access is allowed only to administrators.
// admin_guard.php also loads the database connection.
require_once __DIR__ . '/../includes/admin_guard.php';

// Dashboard statistics from MySQL.
$stats = [
    'users' => (int) $pdo->query(
        "SELECT COUNT(*) FROM users"
    )->fetchColumn(),

    'books' => (int) $pdo->query(
        "SELECT COUNT(*) FROM books"
    )->fetchColumn(),

    'reservations' => (int) $pdo->query(
        "SELECT COUNT(*) FROM reservations
         WHERE status = 'active'"
    )->fetchColumn(),

    'messages' => (int) $pdo->query(
        "SELECT COUNT(*) FROM messages
         WHERE is_read = 0"
    )->fetchColumn()
];

// Five most recently registered users.
$recentUsers = $pdo->query("
    SELECT id, login, first_name, last_name,
           email, created_at
    FROM users
    ORDER BY created_at DESC, id DESC
    LIMIT 5
")->fetchAll();

// Five most recent reservations.
$recentReservations = $pdo->query("
    SELECT r.id, r.status, r.created_at,
           u.login, b.title
    FROM reservations r
    INNER JOIN users u ON r.user_id = u.id
    INNER JOIN books b ON r.book_id = b.id
    ORDER BY r.created_at DESC, r.id DESC
    LIMIT 5
")->fetchAll();

// Escape output to prevent XSS.
function dashboard_h($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Knihovna</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main>
    <div class="container">

        <h1 class="page-title">Admin Dashboard</h1>
        <p>Přehled aktuálních údajů knihovny</p>

        <!-- Dynamic statistics -->
        <div class="dashboard-grid">

            <div class="stat-card">
                <h3>Počet uživatelů</h3>
                <div class="stat-number">
                    <?= $stats['users'] ?>
                </div>
            </div>

            <div class="stat-card">
                <h3>Počet knih</h3>
                <div class="stat-number">
                    <?= $stats['books'] ?>
                </div>
            </div>

            <div class="stat-card">
                <h3>Aktivní rezervace</h3>
                <div class="stat-number">
                    <?= $stats['reservations'] ?>
                </div>
            </div>

            <div class="stat-card">
                <h3>Nepřečtené zprávy</h3>
                <div class="stat-number">
                    <?= $stats['messages'] ?>
                </div>
            </div>

        </div>

        <!-- Latest registered users -->
        <section class="card">
            <h2>Poslední registrace</h2>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Uživatel</th>
                            <th>E-mail</th>
                            <th>Datum registrace</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($recentUsers)): ?>
                        <tr>
                            <td colspan="3">
                                Zatím žádní uživatelé.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td>
                                    <?= dashboard_h(
                                        $user['first_name'] . ' ' .
                                        $user['last_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= dashboard_h($user['email']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd.m.Y H:i',
                                        strtotime($user['created_at'])
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Latest reservations -->
        <section class="card">
            <h2>Poslední rezervace</h2>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Uživatel</th>
                            <th>Kniha</th>
                            <th>Stav</th>
                            <th>Datum</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (empty($recentReservations)): ?>
                        <tr>
                            <td colspan="4">
                                Zatím žádné rezervace.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentReservations as $r): ?>
                            <tr>
                                <td>
                                    <?= dashboard_h($r['login']) ?>
                                </td>

                                <td>
                                    <?= dashboard_h($r['title']) ?>
                                </td>

                                <td>
                                    <?php
                                    $statusLabels = [
                                        'active' => 'Aktivní',
                                        'cancelled' => 'Zrušená',
                                        'completed' => 'Dokončená'
                                    ];

                                    $status = $r['status'];
                                    ?>
                                    <span class="badge">
                                        <?= dashboard_h(
                                            $statusLabels[$status]
                                            ?? $status
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= date(
                                        'd.m.Y H:i',
                                        strtotime($r['created_at'])
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>
</body>
</html>
