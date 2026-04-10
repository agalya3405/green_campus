-- Create sample faculty user
INSERT INTO users (name, email, password, role) 
VALUES ('Sample Faculty', 'faculty@college.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty');
-- Password is 'faculty123' (hashed)

-- Get the ID of the new faculty member (Assuming it's the last inserted ID, or you can manually check)
-- For this script, we'll assume the ID is AUTO_INCREMENT. In a real scenario, we'd fetch it.
-- Let's insert a dummy idea assigned to this faculty member (assuming User ID 1 exists as a student)

INSERT INTO ideas (user_id, title, description, category, status, assigned_faculty_id, created_at)
VALUES 
(1, 'Solar Powered Benches', 'Install benches with solar panels to charge devices.', 'Energy', 'Approved', 
(SELECT id FROM users WHERE email = 'faculty@college.com' LIMIT 1), NOW()),

(1, 'Rainwater Harvesting System', 'Implement a system to collect rainwater for gardening.', 'Water', 'Approved', 
(SELECT id FROM users WHERE email = 'faculty@college.com' LIMIT 1), NOW());
