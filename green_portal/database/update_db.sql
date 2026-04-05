-- update_db.sql
USE green_innovation;

-- 1. Add progress and review columns to ideas table
ALTER TABLE ideas 
ADD COLUMN progress_percentage INT DEFAULT 0,
ADD COLUMN review1_status VARCHAR(20) DEFAULT 'Pending',
ADD COLUMN review1_remarks TEXT,
ADD COLUMN review2_status VARCHAR(20) DEFAULT 'Pending',
ADD COLUMN review2_remarks TEXT,
ADD COLUMN final_review_status VARCHAR(20) DEFAULT 'Pending',
ADD COLUMN final_review_remarks TEXT;

-- Rename existing faculty columns to faculty
ALTER TABLE ideas CHANGE COLUMN assigned_faculty_id assigned_faculty_id INT DEFAULT NULL;
ALTER TABLE ideas CHANGE COLUMN faculty_remarks faculty_remarks TEXT DEFAULT NULL;

-- 2. Modify users.role ENUM and update existing values
-- First, update any 'faculty' to 'faculty' if they exist (though we'll delete them anyway)
UPDATE users SET role = 'faculty' WHERE role = 'faculty';

ALTER TABLE users 
MODIFY COLUMN role ENUM('student', 'faculty', 'admin') NOT NULL DEFAULT 'student';

-- 3. Delete all Faculty (now Faculty) and Student accounts
DELETE FROM users WHERE role IN ('student', 'faculty');

-- 4. Reset ideas table as well since users are gone
DELETE FROM ideas;
