-- Sample staff user and ideas assigned to staff
-- Run this in phpMyAdmin after staff_module_migration.sql
-- Password: staff123 (hashed with password_hash)

USE green_innovation;

-- Insert sample staff (ignore if already exists)
INSERT INTO users (name, email, password, role) VALUES
('Campus Staff', 'staff@college.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff')
ON DUPLICATE KEY UPDATE role = 'staff';

-- Get staff user id (run after the insert; use the id returned or from users table)
-- Then assign some ideas to this staff. Example (replace 2 with actual staff user id from users table):
-- UPDATE ideas SET assigned_staff_id = 2, assigned_to = 'Campus Staff', status = 'Approved' WHERE id IN (1,2,3);

-- If you have no ideas yet, create sample ideas first (submitted by a user), then run:
-- UPDATE ideas SET assigned_staff_id = (SELECT id FROM users WHERE email = 'staff@college.com' LIMIT 1), assigned_to = 'Campus Staff', status = 'Approved' LIMIT 2;
