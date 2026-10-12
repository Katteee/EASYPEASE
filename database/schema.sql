CREATE DATABASE IF NOT EXISTS library_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;

-- 1. Таблица пользователей
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       login VARCHAR(50) NOT NULL UNIQUE,
                       password VARCHAR(255) NOT NULL,
                       first_name VARCHAR(50) NOT NULL,
                       last_name VARCHAR(50) NOT NULL,
                       email VARCHAR(100) NOT NULL UNIQUE,
                       phone VARCHAR(20) NOT NULL,
                       gender ENUM('male', 'female', 'other') NOT NULL,
                       avatar VARCHAR(255) DEFAULT 'default_avatar.jpg',
                       role ENUM('user', 'admin') DEFAULT 'user',
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Таблица книг
CREATE TABLE books (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       title VARCHAR(255) NOT NULL,
                       author VARCHAR(150) NOT NULL,
                       isbn VARCHAR(20) UNIQUE NOT NULL,
                       genre VARCHAR(50) NOT NULL,
                       year_published INT NOT NULL,
                       description TEXT,
                       total_copies INT NOT NULL DEFAULT 1,
                       available_copies INT NOT NULL DEFAULT 1,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Таблица бронирований
CREATE TABLE reservations (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              user_id INT NOT NULL,
                              book_id INT NOT NULL,
                              status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                              FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE RESTRICT
);

-- 4. Таблица сообщений
CREATE TABLE messages (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          sender_id INT NOT NULL,
                          receiver_id INT NOT NULL,
                          subject VARCHAR(150) NOT NULL,
                          body TEXT NOT NULL,
                          is_read TINYINT(1) DEFAULT 0,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                          FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);