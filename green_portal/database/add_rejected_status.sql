-- Add 'Rejected' to ideas.status ENUM (run once if Reject gives SQL error)
USE green_innovation;
ALTER TABLE ideas MODIFY COLUMN status ENUM('Pending', 'Approved', 'Rejected', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending';
