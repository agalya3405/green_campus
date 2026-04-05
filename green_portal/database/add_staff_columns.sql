-- Add Faculty module columns to ideas table (if missing).
-- Run in phpMyAdmin → SQL tab. If you get "Duplicate column name", that column already exists — skip that line or ignore.

USE green_innovation;

-- Add assigned_faculty_id if missing
ALTER TABLE ideas ADD COLUMN assigned_faculty_id INT NULL;

-- Add faculty_remarks if missing  
ALTER TABLE ideas ADD COLUMN faculty_remarks TEXT NULL;

-- Optional: link assigned_faculty_id to users (skip if you get "Duplicate foreign key")
ALTER TABLE ideas ADD CONSTRAINT fk_ideas_assigned_faculty FOREIGN KEY (assigned_faculty_id) REFERENCES users(id) ON DELETE SET NULL;

-- Optional: index for faculty dashboard (skip if you get "Duplicate key name")
CREATE INDEX idx_ideas_assigned_faculty_id ON ideas(assigned_faculty_id);
