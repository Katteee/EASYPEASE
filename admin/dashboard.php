<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Knihovna</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include '../includes/navbar.php'; ?>

<main>
    <div class="container">

        <h1 class="page-title">Admin Dashboard</h1>

        <div class="dashboard-grid">

            <div class="stat-card">
                <h3>Počet uživatelů</h3>
                <div class="stat-number">120</div>
            </div>

            <div class="stat-card">
                <h3>Počet knih</h3>
                <div class="stat-number">350</div>
            </div>

            <div class="stat-card">
                <h3>Aktivní rezervace</h3>
                <div class="stat-number">25</div>
            </div>

            <div class="stat-card">
                <h3>Nepřečtené zprávy</h3>
                <div class="stat-number">12</div>
            </div>

        </div>


        <div class="card">
            <h2>Poslední registrace</h2>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Uživatel</th>
                            <th>E-mail</th>
                            <th>Datum</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Jan Novák</td>
                            <td>jan@example.cz</td>
                            <td>12. 10. 2026</td>
                        </tr>

                        <tr>
                            <td>Eva Malá</td>
                            <td>eva@example.cz</td>
                            <td>11. 10. 2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <div class="card">
            <h2>Poslední rezervace</h2>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Uživatel</th>
                            <th>Kniha</th>
                            <th>Stav</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Jan Novák</td>
                            <td>1984</td>
                            <td>
                                <span class="badge badge-success">
                                    Aktivní
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>Eva Malá</td>
                            <td>Malý princ</td>
                            <td>
                                <span class="badge badge-warning">
                                    Dokončená
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

</body>
</html>