-- Migration: add failed_attempts, otp_code, otp_expires to users table
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS failed_attempts INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS otp_code VARCHAR(10) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS otp_expires DATETIME DEFAULT NULL;

-- Optional: backup your users table before running this migration
-- CREATE TABLE users_backup AS SELECT * FROM users;
