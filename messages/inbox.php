<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Zprávy | Knihovna</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include '../includes/navbar.php'; ?>

<main>
    <div class="container">

        <h1 class="page-title">Zprávy</h1>

        <div class="message-navigation">

            <a href="inbox.php" class="btn btn-primary">
                Přijaté zprávy
            </a>

            <a href="sent.php" class="btn btn-secondary">
                Odeslané zprávy
            </a>

            <a href="new.php" class="btn btn-success">
                + Nová zpráva
            </a>

        </div>


        <div class="card">

            <h2>Přijaté zprávy</h2>

            <div class="message-item message-unread">

                <div>
                    <strong>Jan Novák</strong>
                    <p>Dotaz na rezervaci knihy</p>
                </div>

                <span class="message-date">
                    12. 10. 2026 14:30
                </span>

            </div>


            <div class="message-item">

                <div>
                    <strong>Eva Malá</strong>
                    <p>Vrácení knihy</p>
                </div>

                <span class="message-date">
                    11. 10. 2026 09:15
                </span>

            </div>

        </div>

    </div>
</main>

</body>
</html>