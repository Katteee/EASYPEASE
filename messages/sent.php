<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Odeslané zprávy | Knihovna</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include '../includes/navbar.php'; ?>

<main>
    <div class="container">

        <h1 class="page-title">Zprávy</h1>

        <!-- Navigace zpráv -->
        <div class="message-navigation">

            <a href="inbox.php" class="btn btn-secondary">
                Přijaté zprávy
            </a>

            <a href="sent.php" class="btn btn-primary">
                Odeslané zprávy
            </a>

            <a href="new.php" class="btn btn-success">
                + Nová zpráva
            </a>

        </div>


        <!-- Odeslané zprávy -->
        <div class="card">

            <h2>Odeslané zprávy</h2>

            <div class="message-item">

                <div>
                    <strong>Komu: Jan Novák</strong>
                    <p>Informace o rezervaci</p>
                </div>

                <span class="message-date">
                    12. 10. 2026 15:45
                </span>

            </div>


            <div class="message-item">

                <div>
                    <strong>Komu: Eva Malá</strong>
                    <p>Potvrzení vrácení knihy</p>
                </div>

                <span class="message-date">
                    11. 10. 2026 10:20
                </span>

            </div>


            <div class="message-item">

                <div>
                    <strong>Komu: Petr Svoboda</strong>
                    <p>Dotaz na dostupnost knihy</p>
                </div>

                <span class="message-date">
                    10. 10. 2026 17:10
                </span>

            </div>

        </div>

    </div>
</main>

</body>
</html>