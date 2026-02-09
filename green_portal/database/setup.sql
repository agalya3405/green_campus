-- Campus Green Innovation Portal - Database Setup
-- Run this in phpMyAdmin or MySQL to create the database and tables

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

-- Table: ideas
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

-- Index for faster lookups (drop first so script can be re-run without error)
DROP INDEX IF EXISTS idx_ideas_user_id ON ideas;
DROP INDEX IF EXISTS idx_ideas_status ON ideas;
DROP INDEX IF EXISTS idx_ideas_assigned_staff_id ON ideas;
CREATE INDEX idx_ideas_user_id ON ideas(user_id);
CREATE INDEX idx_ideas_status ON ideas(status);
CREATE INDEX idx_ideas_assigned_staff_id ON ideas(assigned_staff_id);
