-- Campus Green Innovation Portal - Full setup (no duplicate errors when re-run)
-- Paste this entire block in phpMyAdmin → SQL tab → Go

CREATE DATABASE IF NOT EXISTS green_innovation;
USE green_innovation;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'staff', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: ideas (full structure)
CREATE TABLE IF NOT EXISTS ideas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Approved', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
    assigned_to VARCHAR(100) DEFAULT NULL,
    assigned_staff_id INT DEFAULT NULL,
    staff_remarks TEXT DEFAULT NULL,
    admin_remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Indexes (drop first so no "Duplicate key name" error)
DROP INDEX IF EXISTS idx_ideas_user_id ON ideas;
DROP INDEX IF EXISTS idx_ideas_status ON ideas;
DROP INDEX IF EXISTS idx_ideas_assigned_staff_id ON ideas;
CREATE INDEX idx_ideas_user_id ON ideas(user_id);
CREATE INDEX idx_ideas_status ON ideas(status);
CREATE INDEX idx_ideas_assigned_staff_id ON ideas(assigned_staff_id);

-- Add missing columns to existing ideas table (no error if column already exists)
DROP PROCEDURE IF EXISTS add_ideas_columns_if_missing;
DELIMITER //
CREATE PROCEDURE add_ideas_columns_if_missing()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'green_innovation' AND TABLE_NAME = 'ideas' AND COLUMN_NAME = 'admin_remarks') THEN
        ALTER TABLE ideas ADD COLUMN admin_remarks TEXT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'green_innovation' AND TABLE_NAME = 'ideas' AND COLUMN_NAME = 'updated_at') THEN
        ALTER TABLE ideas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'green_innovation' AND TABLE_NAME = 'ideas' AND COLUMN_NAME = 'assigned_staff_id') THEN
        ALTER TABLE ideas ADD COLUMN assigned_staff_id INT DEFAULT NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'green_innovation' AND TABLE_NAME = 'ideas' AND COLUMN_NAME = 'staff_remarks') THEN
        ALTER TABLE ideas ADD COLUMN staff_remarks TEXT NULL;
    END IF;
END//
DELIMITER ;
CALL add_ideas_columns_if_missing();
DROP PROCEDURE add_ideas_columns_if_missing;
