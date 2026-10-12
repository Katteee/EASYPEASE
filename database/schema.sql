-- EASYPEASE: import into MySQL / MariaDB (for example through phpMyAdmin).
-- Fresh installation only. Existing tables are not deleted or migrated.
-- Names match config/db.php and the feature-auth-and-db PHP backend.
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS library_system
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'PHP password_hash() output',
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default_avatar.jpg',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- admin/books.php: year -> year_published; copies -> total_copies.
-- ISBN, genre and year are optional in that form. Store absent values as NULL.
-- New books should start with available_copies = total_copies.
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) NULL UNIQUE,
    genre VARCHAR(50) NULL,
    year_published INT NULL,
    description TEXT,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_books_copies CHECK (
        total_copies >= 0 AND available_copies >= 0
        AND available_copies <= total_copies
    ),
    INDEX idx_books_title (title),
    INDEX idx_books_author (author),
    INDEX idx_books_genre (genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('active', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservations_book FOREIGN KEY (book_id)
        REFERENCES books(id) ON DELETE RESTRICT,
    INDEX idx_reservations_user_status (user_id, status),
    INDEX idx_reservations_book_status (book_id, status),
    INDEX idx_reservations_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- messages/new.php: recipient -> receiver_id; message -> body.
-- Backend resolves the selected recipient to a users.id value.
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_messages_read CHECK (is_read IN (0, 1)),
    INDEX idx_messages_sender_date (sender_id, created_at),
    INDEX idx_messages_receiver_read_date (receiver_id, is_read, created_at),
    INDEX idx_messages_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Books displayed in the existing administration mockup.
INSERT INTO books
    (title, author, isbn, genre, year_published, description, total_copies, available_copies)
VALUES
    ('1984', 'George Orwell', '9780451524935', 'Román', 1949,
     'Dystopický román o totalitní společnosti a kontrole jednotlivce.', 5, 5),
    ('Malý princ', 'Antoine de Saint-Exupéry', '9780156012195', 'Próza', 1943,
     'Příběh malého prince o přátelství, lásce a odpovědnosti.', 3, 3);

-- Availability changes belong in the reservation backend: use a transaction
-- and a conditional update / row lock when reserving or returning a copy.
-- There are no seeded accounts: register through register.php.
-- To grant admin access to a registered teammate, replace the login below:
-- UPDATE users SET role = 'admin' WHERE login = 'team_member_login';
