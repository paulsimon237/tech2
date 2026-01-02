-- Add firebase_uid column to users table for Google OAuth support
ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(255) UNIQUE DEFAULT NULL AFTER password;
