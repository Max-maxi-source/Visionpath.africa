-- db_mentor_update.sql
USE visionpath_db;

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

ALTER TABLE mentor_profiles
    ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER is_verified_mentor;
UPDATE mentor_profiles SET is_approved = is_verified_mentor WHERE is_approved = 0;
CREATE INDEX idx_mentor_profiles_approval_pathway ON mentor_profiles (is_approved, cbc_pathway_interest);

-- Create project_feedback table for mentor comments & referee opt-in
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

CREATE INDEX idx_project_feedback_project_mentor ON project_feedback (project_id, mentor_id);
CREATE INDEX idx_projects_cbc_created ON projects (cbc_strand, created_at);
CREATE INDEX idx_users_role_school_class ON users (role, school_name, assigned_class);
