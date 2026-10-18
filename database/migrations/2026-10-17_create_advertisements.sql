-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: advertisements table + optional promo video + visitor counting
--
-- Run ONCE against an existing rental_db that already has the `users` table:
--     mysql -u root rental_db < database/migrations/2026-10-17_create_advertisements.sql
-- or import it in phpMyAdmin. Everything is additive and re-runnable: no table,
-- column or row is ever dropped or modified in place.
--
-- Fresh installs do not need this file — setup.php, setup.sql and
-- includes/db.php create the same schema automatically and idempotently.
--
-- TIMEZONE NOTE: start_datetime / end_datetime store wall-clock time in
-- Africa/Addis_Ababa (EAT, UTC+3). Eligibility is checked with the server
-- clock (PHP) — see the documentation block in includes/ads.php.
--
-- PRIVACY NOTE: advertisement_views keeps only sha256(salt|IP) visitor keys;
-- raw IP addresses are never stored.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Advertisements (+ optional promo video file) ────────────────────────────
CREATE TABLE IF NOT EXISTS advertisements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NOT NULL,
    video_path VARCHAR(255) NULL,
    target_url VARCHAR(500) NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',   -- draft | published | inactive
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ad_status_window (status, start_datetime, end_datetime),
    KEY idx_ad_created_by (created_by),
    CONSTRAINT fk_adverts_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 1b. Existing installs that already had the table: add the video column once.
SET @has_video := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'advertisements'
                      AND COLUMN_NAME = 'video_path');
SET @sql := IF(@has_video = 0,
    'ALTER TABLE advertisements ADD COLUMN video_path VARCHAR(255) NULL AFTER image_path',
    'SELECT 1');
PREPARE mig_stmt FROM @sql; EXECUTE mig_stmt; DEALLOCATE PREPARE mig_stmt;

-- 1c. Add the creator foreign key only when it is missing (needs `users`).
SET @has_fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'advertisements'
                   AND CONSTRAINT_NAME = 'fk_adverts_created_by');
SET @sql := IF(@has_fk = 0,
    'ALTER TABLE advertisements ADD CONSTRAINT fk_adverts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1');
PREPARE mig_stmt FROM @sql; EXECUTE mig_stmt; DEALLOCATE PREPARE mig_stmt;

-- 2. Visitor counting ────────────────────────────────────────────────────────
-- One row per counted event. 'view' rows are deduplicated per visitor/day by
-- the application; 'click' rows record a card/CTA click.
CREATE TABLE IF NOT EXISTS advertisement_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    event_type VARCHAR(16) NOT NULL DEFAULT 'view',   -- view | click
    visitor_hash CHAR(64) NOT NULL,                    -- sha256(salt|IP)
    viewed_at DATETIME NOT NULL,                       -- server clock (EAT)
    INDEX idx_adv_views_ad (ad_id, viewed_at),
    INDEX idx_adv_views_hash (visitor_hash),
    INDEX idx_adv_views_kind (event_type, viewed_at),
    CONSTRAINT fk_adv_views_ad
        FOREIGN KEY (ad_id) REFERENCES advertisements(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
