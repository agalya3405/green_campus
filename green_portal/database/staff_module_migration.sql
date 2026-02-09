-- Staff module: add assigned_staff_id and staff_remarks to ideas
-- Run this in phpMyAdmin (SQL tab) once. If you get "duplicate column" errors, columns already exist.

USE green_innovation;

ALTER TABLE ideas ADD COLUMN assigned_staff_id INT NULL;
ALTER TABLE ideas ADD COLUMN staff_remarks TEXT NULL;

ALTER TABLE ideas
ADD CONSTRAINT fk_ideas_assigned_staff
FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_ideas_assigned_staff_id ON ideas(assigned_staff_id);
