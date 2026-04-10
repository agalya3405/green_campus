-- Campus Green Innovation Portal - Database Reset Script
-- This script removes all demo/testing data but keeps the system usable

-- STEP 1: DELETE TRANSACTIONAL DATA
DELETE FROM rewards;
DELETE FROM ideas;

-- STEP 2: REMOVE USERS BUT KEEP ADMIN
DELETE FROM users WHERE role != 'admin';

-- STEP 3: RESET AUTO INCREMENT IDs
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE ideas AUTO_INCREMENT = 1;
ALTER TABLE rewards AUTO_INCREMENT = 1;

-- STEP 4: ENSURE ADMIN EXISTS
-- Check if admin exists, if not create one
INSERT INTO users(name, email, password, role, points)
SELECT 'Administrator', 'admin@green.com', MD5('admin123'), 'admin', 0
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE role = 'admin'
);

-- STEP 5: VERIFY PROBLEMS EXIST
-- Problems should already exist from db.php auto-seeding
-- Just verify count
SELECT COUNT(*) as problem_count FROM problems;

-- Show final state
SELECT 'Users remaining:' as info, COUNT(*) as count FROM users
UNION ALL
SELECT 'Ideas remaining:', COUNT(*) FROM ideas
UNION ALL
SELECT 'Rewards remaining:', COUNT(*) FROM rewards
UNION ALL
SELECT 'Problems available:', COUNT(*) FROM problems;
