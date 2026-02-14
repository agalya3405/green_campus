-- Create sample staff user
INSERT INTO users (name, email, password, role) 
VALUES ('Sample Staff', 'staff@college.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');
-- Password is 'staff123' (hashed)

-- Get the ID of the new staff member (Assuming it's the last inserted ID, or you can manually check)
-- For this script, we'll assume the ID is AUTO_INCREMENT. In a real scenario, we'd fetch it.
-- Let's insert a dummy idea assigned to this staff member (assuming User ID 1 exists as a student)

INSERT INTO ideas (user_id, title, description, category, status, assigned_staff_id, created_at)
VALUES 
(1, 'Solar Powered Benches', 'Install benches with solar panels to charge devices.', 'Energy', 'Approved', 
(SELECT id FROM users WHERE email = 'staff@college.com' LIMIT 1), NOW()),

(1, 'Rainwater Harvesting System', 'Implement a system to collect rainwater for gardening.', 'Water', 'Approved', 
(SELECT id FROM users WHERE email = 'staff@college.com' LIMIT 1), NOW());
