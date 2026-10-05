<!DOCTYPE html>
<html lang="cs">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrace knih | Knihovna</title>

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include '../includes/navbar.php'; ?>

<main>
    <div class="container">

        <h1 class="page-title">Administrace knih</h1>

        <div class="section">
            <button class="btn btn-primary">
                + Přidat knihu
            </button>
        </div>


        <div class="card">

            <h2>Seznam knih</h2>

            <div class="table-wrapper">

                <table class="table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Název</th>
                            <th>Autor</th>
                            <th>ISBN</th>
                            <th>Žánr</th>
                            <th>Počet kusů</th>
                            <th>Akce</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>1</td>
                            <td>1984</td>
                            <td>George Orwell</td>
                            <td>9780451524935</td>
                            <td>Román</td>
                            <td>5</td>

                            <td class="actions">
                                <button class="btn btn-secondary btn-small">
                                    Upravit
                                </button>

                                <button class="btn btn-danger btn-small">
                                    Odstranit
                                </button>
                            </td>
                        </tr>


                        <tr>
                            <td>2</td>
                            <td>Malý princ</td>
                            <td>Antoine de Saint-Exupéry</td>
                            <td>9780156012195</td>
                            <td>Próza</td>
                            <td>3</td>

                            <td class="actions">
                                <button class="btn btn-secondary btn-small">
                                    Upravit
                                </button>

                                <button class="btn btn-danger btn-small">
                                    Odstranit
                                </button>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        <div class="card form-container">

            <h2>Přidat novou knihu</h2>

            <form>

                <div class="form-group">
                    <label for="title">Název knihy</label>
                    <input
                        class="form-control"
                        type="text"
                        id="title"
                        name="title"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="author">Autor</label>
                    <input
                        class="form-control"
                        type="text"
                        id="author"
                        name="author"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input
                        class="form-control"
                        type="text"
                        id="isbn"
                        name="isbn"
                    >
                </div>

                <div class="form-group">
                    <label for="genre">Žánr</label>
                    <input
                        class="form-control"
                        type="text"
                        id="genre"
                        name="genre"
                    >
                </div>

                <div class="form-group">
                    <label for="year">Rok vydání</label>
                    <input
                        class="form-control"
                        type="number"
                        id="year"
                        name="year"
                    >
                </div>

                <div class="form-group">
                    <label for="copies">Počet kusů</label>
                    <input
                        class="form-control"
                        type="number"
                        id="copies"
                        name="copies"
                        min="0"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Popis</label>
                    <textarea
                        class="form-control"
                        id="description"
                        name="description"
                    ></textarea>
                </div>

                <button class="btn btn-success" type="submit">
                    Uložit knihu
                </button>

            </form>

        </div>

    </div>
</main>

</body>
</html>