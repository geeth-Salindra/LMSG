CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    survey_type ENUM('180', '360') NOT NULL,
    question_visibility ENUM('multiple', 'single') NOT NULL DEFAULT 'multiple',
    allow_comments TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_surveys_creator FOREIGN KEY (creator_id) REFERENCES learners(id) ON DELETE CASCADE,
    INDEX idx_surveys_creator_created (creator_id, created_at)
);

CREATE TABLE IF NOT EXISTS survey_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    page_order INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    UNIQUE KEY uq_survey_page_order (survey_id, page_order),
    CONSTRAINT fk_survey_pages_survey FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS survey_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    question_order INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('rating', 'text', 'multiple_choice') NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    score DECIMAL(8, 2) NULL,
    choices JSON NOT NULL,
    scale_min TINYINT UNSIGNED NULL,
    scale_max TINYINT UNSIGNED NULL,
    rating_labels JSON NOT NULL,
    UNIQUE KEY uq_survey_question_order (page_id, question_order),
    CONSTRAINT fk_survey_questions_page FOREIGN KEY (page_id) REFERENCES survey_pages(id) ON DELETE CASCADE
);
