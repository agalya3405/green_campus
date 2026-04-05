-- Faculty module: add assigned_faculty_id and faculty_remarks to ideas
-- Run this in phpMyAdmin (SQL tab) once. If you get "duplicate column" errors, columns already exist.

USE green_innovation;

ALTER TABLE ideas ADD COLUMN assigned_faculty_id INT NULL;
ALTER TABLE ideas ADD COLUMN faculty_remarks TEXT NULL;

ALTER TABLE ideas
ADD CONSTRAINT fk_ideas_assigned_faculty
FOREIGN KEY (assigned_faculty_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_ideas_assigned_faculty_id ON ideas(assigned_faculty_id);
