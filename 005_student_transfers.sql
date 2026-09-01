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

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS transfer_status ENUM('normal', 'transfer_pending') NOT NULL DEFAULT 'normal' AFTER school_id,
    ADD COLUMN IF NOT EXISTS class_id INT NULL AFTER assigned_class,
    ADD CONSTRAINT fk_users_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE users
    ADD INDEX idx_users_transfer_status (transfer_status);
