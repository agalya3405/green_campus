-- Student motivation & reward system
-- Run once on existing database (green_innovation). Skip if columns already exist.

USE green_innovation;

ALTER TABLE users ADD COLUMN points INT DEFAULT 0;
ALTER TABLE ideas ADD COLUMN reward_tag VARCHAR(50) DEFAULT NULL;
