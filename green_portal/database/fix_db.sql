-- COMPREHENSIVE FIX: Run this in phpMyAdmin SQL tab
USE green_innovation;

-- 1. Ensure users role ENUM is updated
-- First update values if they exist
UPDATE users SET role = 'faculty' WHERE role = 'staff';

-- Modify the ENUM
ALTER TABLE users 
MODIFY COLUMN role ENUM('student', 'faculty', 'admin') NOT NULL DEFAULT 'student';

-- 2. Repair 'ideas' table columns
-- Add progress columns if they don't exist
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS progress_percentage INT DEFAULT 0;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS review1_status VARCHAR(20) DEFAULT 'Pending';
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS review1_remarks TEXT;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS review2_status VARCHAR(20) DEFAULT 'Pending';
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS review2_remarks TEXT;
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS final_review_status VARCHAR(20) DEFAULT 'Pending';
ALTER TABLE ideas ADD COLUMN IF NOT EXISTS final_review_remarks TEXT;

-- Handle Staff -> Faculty column renames safely
-- If assigned_staff_id exists, rename it. If not, just ensure assigned_faculty_id exists.
SET @dbname = DATABASE();
SET @tablename = 'ideas';
SET @oldcol1 = 'assigned_staff_id';
SET @newcol1 = 'assigned_faculty_id';
SET @oldcol2 = 'staff_remarks';
SET @newcol2 = 'faculty_remarks';

-- Rename assigned_staff_id to assigned_faculty_id if it exists
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol1) > 0,
    CONCAT('ALTER TABLE ', @tablename, ' CHANGE COLUMN ', @oldcol1, ' ', @newcol1, ' INT DEFAULT NULL'),
    IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @newcol1) = 0,
        CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @newcol1, ' INT DEFAULT NULL'),
        'SELECT 1'
    )
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Rename staff_remarks to faculty_remarks if it exists
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @oldcol2) > 0,
    CONCAT('ALTER TABLE ', @tablename, ' CHANGE COLUMN ', @oldcol2, ' ', @newcol2, ' TEXT DEFAULT NULL'),
    IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @newcol2) = 0,
        CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @newcol2, ' TEXT DEFAULT NULL'),
        'SELECT 1'
    )
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Cleanup: Delete all legacy test users
DELETE FROM users WHERE role IN ('student', 'faculty');

-- 4. Clear data for a fresh start (optional but requested in requirements)
DELETE FROM ideas;
