<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nová zpráva | Knihovna</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<!-- Společná navigace -->
<?php include '../includes/navbar.php'; ?>


<main>

    <div class="container">

        <h1 class="page-title">
            Nová zpráva
        </h1>


        <!-- Navigace zpráv -->
        <div class="message-navigation">

            <a
                href="inbox.php"
                class="btn btn-secondary"
            >
                Přijaté zprávy
            </a>

            <a
                href="sent.php"
                class="btn btn-secondary"
            >
                Odeslané zprávy
            </a>

            <a
                href="new.php"
                class="btn btn-success"
            >
                + Nová zpráva
            </a>

        </div>


        <!-- Formulář nové zprávy -->
        <div class="card">

            <h2>Napsat zprávu</h2>

            <form method="post" action="">

                <!-- Příjemce -->
                <div class="form-group">

                    <label for="recipient">
                        Příjemce
                    </label>

                    <select
                        id="recipient"
                        name="recipient"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Vyberte příjemce
                        </option>

                        <!-- Uživatel může napsat administrátorovi -->
                        <option value="admin">
                            Administrátor
                        </option>

                        <!-- Testovací uživatelé -->
                        <option value="jan">
                            Jan Novák
                        </option>

                        <option value="eva">
                            Eva Malá
                        </option>

                        <option value="petr">
                            Petr Svoboda
                        </option>

                    </select>

                </div>


                <!-- Předmět -->
                <div class="form-group">

                    <label for="subject">
                        Předmět
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        class="form-control"
                        placeholder="Zadejte předmět zprávy"
                        required
                    >

                </div>


                <!-- Zpráva -->
                <div class="form-group">

                    <label for="message">
                        Zpráva
                    </label>

                    <textarea
                        id="message"
                        name="message"
                        class="form-control"
                        rows="7"
                        placeholder="Napište zprávu..."
                        required
                    ></textarea>

                </div>


                <!-- Odeslání -->
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Odeslat zprávu
                </button>

            </form>

        </div>

    </div>

</main>

</body>

</html>