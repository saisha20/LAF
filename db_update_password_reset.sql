-- ============================================================
-- db_update_password_reset.sql
-- Run this ONCE in phpMyAdmin (SQL tab, with foundly_db selected).
--
-- Creates the table that stores password-reset links, connected to
-- the users and admin tables:
--   * a reset for a student fills user_id  (admin_id stays NULL)
--   * a reset for an admin  fills admin_id (user_id  stays NULL)
-- If the account is deleted, its reset links are deleted too.
--
-- Only a hash of each link's secret code is stored, so a copy of
-- the database can never be used to reset someone's password.
--
-- NOTE: this drops the old password_resets table first. Reset links
-- only live for 1 hour, so nothing important is lost.
-- ============================================================

USE foundly_db;

DROP TABLE IF EXISTS password_resets;

CREATE TABLE password_resets (
    reset_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,
    admin_id    INT NULL,
    token_hash  CHAR(64)  NOT NULL,
    expires_at  DATETIME  NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_hash),
    CONSTRAINT fk_resets_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_resets_admin FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
