-- ============================================================
-- db_update_dismiss_match.sql
-- Run this ONCE in phpMyAdmin (SQL tab, with foundly_db selected).
--
-- Adds a column that records why a match was rejected - either by
-- an admin, or by a user who clicked "Not My Item". Safe to run
-- even if you already added this column earlier: IF NOT EXISTS
-- means it does nothing the second time.
-- ============================================================

USE foundly_db;

ALTER TABLE matches
  ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(255) NULL AFTER status;
