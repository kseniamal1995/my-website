CREATE TABLE IF NOT EXISTS names (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    gender ENUM('m', 'f', 'n') NOT NULL,
    popularity INT,
    syllables INT,
    origin_id INT,
    meaning_id VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name),
    KEY idx_gender (gender),
    KEY idx_popularity (popularity),
    KEY idx_syllables (syllables),
    FOREIGN KEY (origin_id) REFERENCES origins(id),
    FOREIGN KEY (meaning_id) REFERENCES meanings(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 