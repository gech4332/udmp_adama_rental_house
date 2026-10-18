-- EthioRent Database Setup (structural only — NO auto admin account)
-- Run this once after cloning, OR just visit setup.php in your browser.

CREATE DATABASE IF NOT EXISTS rental_db;
USE rental_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    phone2 VARCHAR(20) DEFAULT '',
    password VARCHAR(255) NOT NULL,
    is_admin INT DEFAULT 0,
    status INT DEFAULT 0,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verify_token VARCHAR(64) NULL,
    verify_expires DATETIME NULL,
    reset_token VARCHAR(64) NULL,
    reset_expires DATETIME NULL
);

CREATE TABLE IF NOT EXISTS houses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kebele VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    house_number VARCHAR(50),
    region VARCHAR(100) NOT NULL DEFAULT '',
    city VARCHAR(100) NOT NULL DEFAULT '',
    latitude DECIMAL(9,6) NULL,
    longitude DECIMAL(9,6) NULL,
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_id INT NOT NULL,
    status INT DEFAULT 0,
    type VARCHAR(20) DEFAULT 'new',
    changes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS house_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_house (house_id)
);

CREATE TABLE IF NOT EXISTS admin_invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT
);

CREATE TABLE IF NOT EXISTS rental_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    house_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, attempt_at)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'info',
    title VARCHAR(120) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read)
);

CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS house_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    amenity_id INT NOT NULL,
    INDEX idx_house (house_id),
    UNIQUE KEY unique_house_amenity (house_id, amenity_id)
);

CREATE TABLE IF NOT EXISTS app_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT
);

CREATE TABLE IF NOT EXISTS advertisements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NOT NULL,
    video_path VARCHAR(255) NULL,
    target_url VARCHAR(500) NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ad_status_window (status, start_datetime, end_datetime),
    KEY idx_ad_created_by (created_by),
    CONSTRAINT fk_adverts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Advertisement visitor statistics: 'view' = the card was really seen by a
-- visitor, 'click' = the card/CTA was clicked. visitor_hash = sha256(salt|IP),
-- so raw IP addresses are never stored. Deleting an ad deletes its history.
CREATE TABLE IF NOT EXISTS advertisement_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    event_type VARCHAR(16) NOT NULL DEFAULT 'view',
    visitor_hash CHAR(64) NOT NULL,
    viewed_at DATETIME NOT NULL,
    INDEX idx_adv_views_ad (ad_id, viewed_at),
    INDEX idx_adv_views_hash (visitor_hash),
    INDEX idx_adv_views_kind (event_type, viewed_at),
    CONSTRAINT fk_adv_views_ad FOREIGN KEY (ad_id) REFERENCES advertisements(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Owner service-fee payments. An owner must pay a fee (default 2% of the
-- first month's rent) before they may accept a tenant's rental request.
-- rent_snapshot is captured server-side so the amount cannot be tampered
-- with from the browser. See includes/payments.php.
CREATE TABLE IF NOT EXISTS rental_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_request_id INT NOT NULL,
    house_id INT NOT NULL,
    owner_id INT NOT NULL,
    tenant_id INT NOT NULL,
    rent_snapshot INT NOT NULL,
    fee_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0200,
    fee_amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(8) NOT NULL DEFAULT 'ETB',
    provider VARCHAR(30) NOT NULL DEFAULT 'test',
    reference VARCHAR(64) NOT NULL,
    gateway_tx_id VARCHAR(128) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    verification_source VARCHAR(20) NULL,
    pay_url VARCHAR(500) NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payment_reference (reference),
    KEY idx_rp_request (rental_request_id),
    KEY idx_rp_owner (owner_id),
    KEY idx_rp_status (status),
    CONSTRAINT fk_rp_request FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_house   FOREIGN KEY (house_id)           REFERENCES houses(id)           ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_owner   FOREIGN KEY (owner_id)           REFERENCES users(id)            ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_tenant  FOREIGN KEY (tenant_id)          REFERENCES users(id)            ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed amenities
INSERT INTO amenities (name, icon, sort_order) VALUES
('Water Supply', 'fas fa-droplet', 1),
('Electricity', 'fas fa-bolt', 2),
('WiFi', 'fas fa-wifi', 3),
('Parking', 'fas fa-square-parking', 4),
('Furnished', 'fas fa-couch', 5),
('Air Conditioning', 'fas fa-snowflake', 6),
('Security Guard', 'fas fa-shield-halved', 7),
('CCTV', 'fas fa-video', 8),
('Generator', 'fas fa-car-battery', 9),
('Elevator', 'fas fa-arrow-up', 10),
('Garden', 'fas fa-seedling', 11),
('Swimming Pool', 'fas fa-person-swimming', 12),
('Laundry', 'fas fa-shirt', 13),
('Kitchen', 'fas fa-utensils', 14),
('Balcony', 'fas fa-house-chimney', 15);

-- NOTE: No admin account is created here for security.
-- After running setup.php, visit the generated admin setup key page,
-- then register your first account with that key to become admin.
