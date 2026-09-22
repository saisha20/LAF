-- ============================================================
-- db_update_reject_reason.sql
-- Run this ONCE in phpMyAdmin (SQL tab, with foundly_db selected).
--
-- Adds a column to store why the admin rejected a match, so the
-- notification sent to the user can explain the reason.
-- ============================================================

USE foundly_db;

ALTER TABLE matches
  ADD COLUMN rejection_reason VARCHAR(255) NULL AFTER status;
