-- Add Staff module columns to ideas table (if missing).
-- Run in phpMyAdmin → SQL tab. If you get "Duplicate column name", that column already exists — skip that line or ignore.

USE green_innovation;

-- Add assigned_staff_id if missing
ALTER TABLE ideas ADD COLUMN assigned_staff_id INT NULL;

-- Add staff_remarks if missing  
ALTER TABLE ideas ADD COLUMN staff_remarks TEXT NULL;

-- Optional: link assigned_staff_id to users (skip if you get "Duplicate foreign key")
ALTER TABLE ideas ADD CONSTRAINT fk_ideas_assigned_staff FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL;

-- Optional: index for staff dashboard (skip if you get "Duplicate key name")
CREATE INDEX idx_ideas_assigned_staff_id ON ideas(assigned_staff_id);
