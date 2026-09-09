-- ============================================================
-- db_update_admin.sql
-- Run AFTER foundly.sql has already been imported (needs the
-- matches table to exist first).
--
-- This creates the separate `admin` table and repoints the
-- matches.verified_by foreign key at it instead of `users`,
-- matching the current project architecture.
-- ============================================================

USE foundly_db;

CREATE TABLE admin (
    admin_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    phone          VARCHAR(10)  NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           VARCHAR(20)  NOT NULL DEFAULT 'admin',
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE matches DROP FOREIGN KEY fk_matches_admin;

ALTER TABLE matches
  ADD CONSTRAINT fk_matches_admin
  FOREIGN KEY (verified_by) REFERENCES admin(admin_id)
  ON DELETE SET NULL;
