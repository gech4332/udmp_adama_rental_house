-- AdamaRent Database Setup (structural only — NO auto admin account)
-- Run this once after cloning, OR just visit setup.php in your browser.

CREATE DATABASE IF NOT EXISTS rental_db;
USE rental_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin INT DEFAULT 0,
    status INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kebele VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    house_number VARCHAR(50),
    category VARCHAR(50) NOT NULL,
    amount INT NOT NULL,
    phone VARCHAR(50) NOT NULL,
    map_link TEXT,
    image VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    video_file VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Pending',
    is_approved INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    delete_key VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_id INT NOT NULL,
    status INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- NOTE: No admin account is created here for security.
-- After running setup.php, visit the generated admin setup key page,
-- then register your first account with that key to become admin.
