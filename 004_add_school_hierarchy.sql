-- 004_add_school_hierarchy.sql
-- VisionPath Africa multi-tenancy and junior-admin migration
-- Run after the base schema is in place.

START TRANSACTION;

-- 1) Create schools table first so users.school_id can reference it.
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    county VARCHAR(100) NOT NULL,
    sub_county VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_name_county (school_name, county)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Update users.role to support super admin / junior admin multi-tenancy.
ALTER TABLE users
    MODIFY role ENUM('super_admin', 'junior_admin', 'teacher', 'student', 'parent', 'mentor') NOT NULL;

-- 3) Add school_id to users if it does not already exist.
ALTER TABLE users
    ADD COLUMN school_id INT NULL AFTER role;

-- 4) Backfill school_id from the current school_name values where possible.
--    For existing schools that have no county, the migration assigns a default county.
INSERT INTO schools (school_name, county, sub_county)
SELECT DISTINCT
    u.school_name,
    'Unassigned',
    NULL
FROM users u
WHERE u.school_name IS NOT NULL
  AND u.school_name <> ''
ON DUPLICATE KEY UPDATE school_name = VALUES(school_name);

UPDATE users u
JOIN schools s ON s.school_name = u.school_name
SET u.school_id = s.id
WHERE u.school_id IS NULL
  AND u.school_name IS NOT NULL
  AND u.school_name <> '';

-- 5) Add foreign key constraint between users and schools.
ALTER TABLE users
    ADD CONSTRAINT fk_users_school
    FOREIGN KEY (school_id) REFERENCES schools(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- 6) Create classes table for school-specific class assignments.
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    assigned_teacher_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_class (school_id, class_name),
    CONSTRAINT fk_classes_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_classes_teacher
        FOREIGN KEY (assigned_teacher_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) Backfill classes from existing teacher assignments.
INSERT INTO classes (school_id, class_name, assigned_teacher_id)
SELECT DISTINCT
    s.id,
    u.assigned_class,
    u.id
FROM users u
JOIN schools s ON s.school_name = u.school_name
WHERE u.role = 'teacher'
  AND u.assigned_class IS NOT NULL
  AND u.assigned_class <> ''
ON DUPLICATE KEY UPDATE assigned_teacher_id = VALUES(assigned_teacher_id);

-- 8) Add useful performance indexes.
CREATE INDEX idx_users_school_id ON users (school_id);
CREATE INDEX idx_users_role_school ON users (role, school_id);
CREATE INDEX idx_classes_school ON classes (school_id);
CREATE INDEX idx_classes_teacher ON classes (assigned_teacher_id);

COMMIT;
