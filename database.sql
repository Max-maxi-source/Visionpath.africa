CREATE DATABASE IF NOT EXISTS visionpath_db;
USE visionpath_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_number VARCHAR(50) UNIQUE NULL,
    name VARCHAR(100) NOT NULL,
    school_name VARCHAR(100) NULL,
    assigned_class VARCHAR(100) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'parent', 'mentor') NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users MODIFY school_name VARCHAR(100) NULL;
ALTER TABLE users MODIFY assigned_class VARCHAR(100) NULL;
ALTER TABLE users ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE verification_codes ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mentor_profiles (
    user_id INT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    institution_company VARCHAR(150) NOT NULL,
    job_title VARCHAR(100) NOT NULL,
    cbc_pathway_interest ENUM('STEM','Social Sciences','Arts & Sports Science') NOT NULL,
    linkedin_url VARCHAR(255) NOT NULL DEFAULT '',
    mentorship_capacity INT NOT NULL DEFAULT 5,
    professional_bio TEXT,
    is_verified_mentor TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_mentor_profiles_approval_pathway ON mentor_profiles (is_approved, cbc_pathway_interest);

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    uploaded_by_teacher_id INT NOT NULL,
    project_title VARCHAR(150) NOT NULL,
    project_description TEXT NOT NULL,
    cbc_strand VARCHAR(100) NOT NULL,
    project_documentation VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_projects_teacher FOREIGN KEY (uploaded_by_teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE projects ENGINE=InnoDB;
CREATE INDEX idx_projects_cbc_created ON projects (cbc_strand, created_at);

CREATE TABLE IF NOT EXISTS project_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    mentor_id INT NOT NULL,
    feedback_text TEXT NOT NULL,
    is_referee TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_projects_cbc_created ON projects (cbc_strand, created_at);
CREATE INDEX idx_project_feedback_project_mentor ON project_feedback (project_id, mentor_id);
CREATE INDEX idx_users_role_school_class ON users (role, school_name, assigned_class);

ALTER TABLE project_feedback ENGINE=InnoDB;
CREATE INDEX idx_project_feedback_project_mentor ON project_feedback (project_id, mentor_id);
CREATE INDEX idx_users_role_school_class ON users (role, school_name, assigned_class);
