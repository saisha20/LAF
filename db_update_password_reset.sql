-- ============================================================
-- db_update_password_reset.sql
-- Run this ONCE in phpMyAdmin (SQL tab, with foundly_db selected).
--
-- Creates the table that stores password-reset links.
-- Only a hash of each link's secret code is stored, so a copy
-- of the database can never be used to reset someone's password.
-- ============================================================

USE foundly_db;

CREATE TABLE IF NOT EXISTS password_resets (
    reset_id      INT AUTO_INCREMENT PRIMARY KEY,
    account_type  ENUM('user','admin') NOT NULL,
    account_id    INT          NOT NULL,
    token_hash    CHAR(64)     NOT NULL,
    expires_at    DATETIME     NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token   (token_hash),
    INDEX idx_account (account_type, account_id)
) ENGINE=InnoDB;
