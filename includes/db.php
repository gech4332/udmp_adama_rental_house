<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// DB credentials come from config_secrets.php (gitignored so each machine/host
// uses its own values). Falls back to local XAMPP defaults when not defined.
if (file_exists(__DIR__ . '/../config/config_secrets.php')) {
    require_once __DIR__ . '/../config/config_secrets.php';
}
$host   = defined('DB_HOST') ? DB_HOST : "localhost";
$user   = defined('DB_USER') ? DB_USER : "root";
$pass   = defined('DB_PASS') ? DB_PASS : "";
$dbname = defined('DB_NAME') ? DB_NAME : "rental_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, attempt_at)
)");

// Idempotent migration: add password-reset columns if this install predates them.
$reset_col = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($reset_col && mysqli_num_rows($reset_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL, ADD COLUMN reset_expires DATETIME NULL");
}

// Idempotent migration: add region/city columns if this install predates them.
$region_col = @mysqli_query($conn, "SHOW COLUMNS FROM houses LIKE 'region'");
if ($region_col && mysqli_num_rows($region_col) == 0) {
    mysqli_query($conn, "ALTER TABLE houses ADD COLUMN region VARCHAR(100) NOT NULL DEFAULT '', ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT ''");
}

// Earlier builds shipped a `country` column before it became `city`.
$city_col = @mysqli_query($conn, "SHOW COLUMNS FROM houses LIKE 'city'");
if (!$city_col || mysqli_num_rows($city_col) == 0) {
    $country_col = @mysqli_query($conn, "SHOW COLUMNS FROM houses LIKE 'country'");
    if ($country_col && mysqli_num_rows($country_col) > 0) {
        mysqli_query($conn, "ALTER TABLE houses CHANGE COLUMN country city VARCHAR(100) NOT NULL DEFAULT ''");
    }
}

// Idempotent migration: add map coordinates if this install predates them.
$lat_col = @mysqli_query($conn, "SHOW COLUMNS FROM houses LIKE 'latitude'");
if ($lat_col && mysqli_num_rows($lat_col) == 0) {
    mysqli_query($conn, "ALTER TABLE houses ADD COLUMN latitude DECIMAL(9,6) NULL, ADD COLUMN longitude DECIMAL(9,6) NULL");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS house_amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    house_id INT NOT NULL,
    amenity_id INT NOT NULL,
    INDEX idx_house (house_id),
    UNIQUE KEY unique_house_amenity (house_id, amenity_id)
)");

$amenity_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM amenities"))[0];
if ($amenity_count == 0) {
    $seeds = [
        ['Water Supply', 'fas fa-droplet', 1],
        ['Electricity', 'fas fa-bolt', 2],
        ['WiFi', 'fas fa-wifi', 3],
        ['Parking', 'fas fa-square-parking', 4],
        ['Furnished', 'fas fa-couch', 5],
        ['Air Conditioning', 'fas fa-snowflake', 6],
        ['Security Guard', 'fas fa-shield-halved', 7],
        ['CCTV', 'fas fa-video', 8],
        ['Generator', 'fas fa-car-battery', 9],
        ['Elevator', 'fas fa-arrow-up', 10],
        ['Garden', 'fas fa-seedling', 11],
        ['Swimming Pool', 'fas fa-person-swimming', 12],
        ['Laundry', 'fas fa-shirt', 13],
        ['Kitchen', 'fas fa-utensils', 14],
        ['Balcony', 'fas fa-house-chimney', 15],
    ];
    foreach ($seeds as $s) {
        $name = mysqli_real_escape_string($conn, $s[0]);
        $icon = mysqli_real_escape_string($conn, $s[1]);
        mysqli_query($conn, "INSERT INTO amenities (name, icon, sort_order) VALUES ('$name', '$icon', {$s[2]})");
    }
}

// Idempotent migration: advertisements table (admin advertising feature).
include_once __DIR__ . '/ads.php';
ads_ensure_schema($conn);

// Idempotent migration: owner service-fee payments (secure rental acceptance).
include_once __DIR__ . '/payments.php';
payments_ensure_schema($conn);

