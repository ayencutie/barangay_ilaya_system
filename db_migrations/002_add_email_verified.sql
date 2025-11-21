-- Migration: add email_verified column
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 0;

-- Backup recommended before running.
-- CREATE TABLE users_backup AS SELECT * FROM users;
