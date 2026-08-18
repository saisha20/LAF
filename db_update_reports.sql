
USE foundly_db;

ALTER TABLE reports
  ADD COLUMN color          VARCHAR(50)  NULL AFTER description,
  ADD COLUMN brand          VARCHAR(50)  NULL AFTER color,
  ADD COLUMN reward_amount  DECIMAL(10,2) NULL AFTER brand,
  ADD COLUMN condition_status ENUM('new','good','fair','poor') NULL AFTER reward_amount,
  ADD COLUMN notes          TEXT NULL AFTER location,
  ADD COLUMN is_public      TINYINT(1) NOT NULL DEFAULT 1 AFTER status,
  ADD COLUMN photo_data     LONGBLOB NULL,
  ADD COLUMN photo_type     VARCHAR(100) NULL;
