-- ─────────────────────────────────────────────────────────────────────────────
-- Migration: owner service-fee payments for secure rental-request acceptance
--
-- Run ONCE against an existing rental_db that already has the `users`,
-- `houses` and `rental_requests` tables:
--     mysql -u root rental_db < database/migrations/2026-10-17_create_rental_payments.sql
-- or import it in phpMyAdmin. Everything is additive and re-runnable: no table,
-- column or row is ever dropped or modified in place.
--
-- Fresh installs do not need this file — includes/db.php creates the same
-- schema automatically and idempotently on every boot (payments_ensure_schema).
--
-- BUSINESS RULE: an owner must pay a service fee = 2% of the first month's rent
-- before they may accept a tenant's rental request. The rent and fee are
-- snapshotted on the payment row (rent_snapshot / fee_rate / fee_amount) so the
-- amount is always derived server-side and can never be tampered with.
--
-- PAYMENT STATUS: pending | verified | failed | cancelled | expired.
-- verification_source distinguishes gateway-verified payments from an admin's
-- manual verification (kept separate on purpose).
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. Payment records ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rental_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_request_id INT NOT NULL,
    house_id INT NOT NULL,
    owner_id INT NOT NULL,
    tenant_id INT NOT NULL,
    rent_snapshot INT NOT NULL,                         -- first month's rent (ETB)
    fee_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0200,      -- 2%
    fee_amount DECIMAL(12,2) NOT NULL,                  -- rent_snapshot * fee_rate
    currency VARCHAR(8) NOT NULL DEFAULT 'ETB',
    provider VARCHAR(30) NOT NULL DEFAULT 'test',       -- chapa | test | manual
    reference VARCHAR(64) NOT NULL,                     -- unique per attempt
    gateway_tx_id VARCHAR(128) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',      -- pending|verified|failed|cancelled|expired
    verification_source VARCHAR(20) NULL,               -- gateway | manual | test
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

-- 2. Acceptance timestamp on the rental request (additive only) ───────────────
SET @has_accepted := (SELECT COUNT(*) FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'rental_requests'
                         AND COLUMN_NAME = 'accepted_at');
SET @sql := IF(@has_accepted = 0,
    'ALTER TABLE rental_requests ADD COLUMN accepted_at DATETIME NULL',
    'SELECT 1');
PREPARE mig_stmt FROM @sql; EXECUTE mig_stmt; DEALLOCATE PREPARE mig_stmt;
