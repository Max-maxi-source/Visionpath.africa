USE visionpath_db;

ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE verification_codes ENGINE=InnoDB;
ALTER TABLE projects ENGINE=InnoDB;
ALTER TABLE projects ADD INDEX idx_projects_cbc_created (cbc_strand, created_at);
ALTER TABLE project_feedback ADD INDEX idx_project_feedback_project_mentor (project_id, mentor_id);
ALTER TABLE users ADD INDEX idx_users_role_school_class (role, school_name, assigned_class);

-- Users table updates
ALTER TABLE users ADD COLUMN assigned_class VARCHAR(100) NULL AFTER school_name;
ALTER TABLE users CHANGE COLUMN parent_email email VARCHAR(100) NOT NULL;

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
