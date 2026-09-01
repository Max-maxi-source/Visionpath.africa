USE visionpath_db;

ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE verification_codes ENGINE=InnoDB;
ALTER TABLE projects ENGINE=InnoDB;
ALTER TABLE projects ADD INDEX idx_projects_cbc_created (cbc_strand, created_at);
ALTER TABLE project_feedback ADD INDEX idx_project_feedback_project_mentor (project_id, mentor_id);
ALTER TABLE users ADD INDEX idx_users_role_school_class (role, school_name, assigned_class);

-- Users table updates
SELECT COUNT(*) INTO @has_school_id
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'school_id';
SET @school_sql = IF(@has_school_id = 0, 'ALTER TABLE users ADD COLUMN school_id INT NULL AFTER role', 'SELECT 1');
PREPARE school_stmt FROM @school_sql; EXECUTE school_stmt; DEALLOCATE PREPARE school_stmt;

SELECT COUNT(*) INTO @has_transfer_status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'transfer_status';
SET @transfer_sql = IF(@has_transfer_status = 0, "ALTER TABLE users ADD COLUMN transfer_status ENUM('normal', 'transfer_pending') NOT NULL DEFAULT 'normal' AFTER school_id", 'SELECT 1');
PREPARE transfer_stmt FROM @transfer_sql; EXECUTE transfer_stmt; DEALLOCATE PREPARE transfer_stmt;

SELECT COUNT(*) INTO @has_class_id
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'class_id';
SET @class_sql = IF(@has_class_id = 0, 'ALTER TABLE users ADD COLUMN class_id INT NULL AFTER assigned_class', 'SELECT 1');
PREPARE class_stmt FROM @class_sql; EXECUTE class_stmt; DEALLOCATE PREPARE class_stmt;

ALTER TABLE users ADD COLUMN assigned_class VARCHAR(100) NULL AFTER school_name;
ALTER TABLE users CHANGE COLUMN parent_email email VARCHAR(100) NOT NULL;

-- Ensure class assignments and school hierarchy are present
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    county VARCHAR(100) NOT NULL,
    sub_county VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_name_county (school_name, county)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    assigned_teacher_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_school_class (school_id, class_name),
    CONSTRAINT fk_classes_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_classes_teacher FOREIGN KEY (assigned_teacher_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    from_school_id INT NOT NULL,
    to_school_id INT NOT NULL,
    requested_by_admin_id INT NOT NULL,
    approved_by_admin_id INT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    request_reason TEXT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    CONSTRAINT fk_student_transfers_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_transfers_from_school FOREIGN KEY (from_school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_transfers_to_school FOREIGN KEY (to_school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_transfers_request_admin FOREIGN KEY (requested_by_admin_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_student_transfers_approved_admin FOREIGN KEY (approved_by_admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verification codes table updates
ALTER TABLE verification_codes CHANGE COLUMN parent_email email VARCHAR(100) NOT NULL;

-- Projects table updates
-- Drop the existing foreign key and column
ALTER TABLE projects DROP FOREIGN KEY projects_ibfk_1;
ALTER TABLE projects DROP COLUMN student_admission_number;

-- Add new student_id foreign key
ALTER TABLE projects ADD COLUMN student_id INT NOT NULL AFTER id;
ALTER TABLE projects ADD CONSTRAINT fk_student_id FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE;

-- Rename other columns
ALTER TABLE projects CHANGE COLUMN title project_title VARCHAR(150) NOT NULL;
ALTER TABLE projects CHANGE COLUMN description project_description TEXT NOT NULL;
ALTER TABLE projects CHANGE COLUMN teacher_id uploaded_by_teacher_id INT NOT NULL;

-- Add the documentation file path column
ALTER TABLE projects ADD COLUMN project_documentation VARCHAR(255) NULL AFTER cbc_strand;

ALTER TABLE projects ENGINE=InnoDB;
