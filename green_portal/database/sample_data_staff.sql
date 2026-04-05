-- Sample faculty user and ideas assigned to faculty
-- Run this in phpMyAdmin after faculty_module_migration.sql
-- Password: faculty123 (hashed with password_hash)

USE green_innovation;

-- Insert sample faculty (ignore if already exists)
INSERT INTO users (name, email, password, role) VALUES
('Campus Faculty', 'faculty@college.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty')
ON DUPLICATE KEY UPDATE role = 'faculty';

-- Get faculty user id (run after the insert; use the id returned or from users table)
-- Then assign some ideas to this faculty. Example (replace 2 with actual faculty user id from users table):
-- UPDATE ideas SET assigned_faculty_id = 2, assigned_to = 'Campus Faculty', status = 'Approved' WHERE id IN (1,2,3);

-- If you have no ideas yet, create sample ideas first (submitted by a user), then run:
-- UPDATE ideas SET assigned_faculty_id = (SELECT id FROM users WHERE email = 'faculty@college.com' LIMIT 1), assigned_to = 'Campus Faculty', status = 'Approved' LIMIT 2;
