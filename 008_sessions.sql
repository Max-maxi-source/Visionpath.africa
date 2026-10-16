CREATE TABLE IF NOT EXISTS sessions (
    id CHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('student','teacher','mentor','parent','super_admin','junior_admin') NOT NULL,
    data JSON NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_sessions_expiry ON sessions (expires_at);
