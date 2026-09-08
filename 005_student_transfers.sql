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
    CONSTRAINT fk_student_transfers_approved_admin FOREIGN KEY (approved_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_transfers_status (status),
    INDEX idx_student_transfers_student (student_id),
    INDEX idx_student_transfers_from_to (from_school_id, to_school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT COUNT(*) INTO @has_transfer_status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'transfer_status';
SET @transfer_sql = IF(@has_transfer_status = 0,
    "ALTER TABLE users ADD COLUMN transfer_status ENUM('normal', 'transfer_pending') NOT NULL DEFAULT 'normal' AFTER school_id",
    'SELECT 1');
PREPARE transfer_stmt FROM @transfer_sql;
EXECUTE transfer_stmt;
DEALLOCATE PREPARE transfer_stmt;

SELECT COUNT(*) INTO @has_class_id
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'class_id';
SET @class_sql = IF(@has_class_id = 0,
    'ALTER TABLE users ADD COLUMN class_id INT NULL AFTER assigned_class',
    'SELECT 1');
PREPARE class_stmt FROM @class_sql;
EXECUTE class_stmt;
DEALLOCATE PREPARE class_stmt;

SELECT COUNT(*) INTO @has_assigned_class
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'assigned_class';
SET @assigned_class_sql = IF(@has_assigned_class = 0,
    'ALTER TABLE users ADD COLUMN assigned_class VARCHAR(100) NULL AFTER school_name',
    'SELECT 1');
PREPARE assigned_class_stmt FROM @assigned_class_sql;
EXECUTE assigned_class_stmt;
DEALLOCATE PREPARE assigned_class_stmt;

CREATE INDEX idx_users_transfer_status ON users (transfer_status);
