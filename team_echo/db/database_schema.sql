CREATE TABLE category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(150) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE management_user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hr', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
);

CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    feedback_text TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT TRUE,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    sentiment_score FLOAT NULL,
    sentiment_label VARCHAR(20) NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT NULL,
    resolved_at DATETIME NULL,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES management_user(user_id) ON DELETE SET NULL
);

CREATE TABLE keyword (
    keyword_id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    keyword_text VARCHAR(100) NOT NULL,
    frequency INT DEFAULT 1,
    FOREIGN KEY (feedback_id) REFERENCES feedback(feedback_id) ON DELETE CASCADE
);

CREATE TABLE sentiment_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    positive_count INT DEFAULT 0,
    neutral_count INT DEFAULT 0,
    negative_count INT DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE
);

CREATE TABLE heatmap_data (
    heatmap_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    heat_intensity FLOAT NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE
);

CREATE TABLE report_history (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    report_path VARCHAR(255),
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES management_user(user_id) ON DELETE CASCADE
);

INSERT INTO category (category_name) VALUES 
('Campus Facilities'),
('Teaching Quality'),
('Student Services'),
('Campus Safety'),
('Social Life'),
('Academic Support'),
('Career Services'),
('Extracurricular Activities'),
('Cultural Events'),
('Campus Environment');

ALTER TABLE management_user ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active';
CREATE INDEX idx_name_email_role ON management_user(name, email, role);
