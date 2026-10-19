USE library_system;
SET NAMES utf8mb4;

INSERT INTO books (title, author, isbn, genre, year_published, description, total_copies, available_copies)
SELECT '1984', 'George Orwell', '9780451524935', 'Román', 1949,
       'Dystopický román o totalitní společnosti a kontrole jednotlivce.', 5, 5
WHERE NOT EXISTS (SELECT 1 FROM books WHERE isbn = '9780451524935' OR (title = '1984' AND author = 'George Orwell'));

INSERT INTO books (title, author, isbn, genre, year_published, description, total_copies, available_copies)
SELECT 'Malý princ', 'Antoine de Saint-Exupéry', '9780156012195', 'Próza', 1943,
       'Poetický příběh o přátelství, lásce a odpovědnosti.', 3, 3
WHERE NOT EXISTS (SELECT 1 FROM books WHERE isbn = '9780156012195' OR (title = 'Malý princ' AND author = 'Antoine de Saint-Exupéry'));

INSERT INTO books (title, author, isbn, genre, year_published, description, total_copies, available_copies)
SELECT 'Hobit', 'J. R. R. Tolkien', '9780261103344', 'Fantasy', 1937,
       'Bilbo Pytlík se vydává na nečekanou cestu s družinou trpaslíků.', 0, 0
WHERE NOT EXISTS (SELECT 1 FROM books WHERE isbn = '9780261103344' OR (title = 'Hobit' AND author = 'J. R. R. Tolkien'));

INSERT INTO books (title, author, isbn, genre, year_published, description, total_copies, available_copies)
SELECT 'R.U.R.', 'Karel Čapek', NULL, 'Drama', 1920,
       'Drama o robotech a odpovědnosti člověka.', 4, 4
WHERE NOT EXISTS (SELECT 1 FROM books WHERE title = 'R.U.R.' AND author = 'Karel Čapek');
