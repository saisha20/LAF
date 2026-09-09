-- ============================================================
-- db_update_claims.sql
-- Run AFTER foundly.sql and db_update_admin.sql.
-- Adds evidence fields to the matches table so a claim can carry
-- a statement of ownership and an optional proof photo.
-- ============================================================

USE foundly_db;

ALTER TABLE matches
  ADD COLUMN claim_statement     TEXT NULL,
  ADD COLUMN claim_evidence_photo LONGBLOB NULL,
  ADD COLUMN claim_evidence_type  VARCHAR(100) NULL;
