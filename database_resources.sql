USE librinet_db;

-- Table for digital resources (e-books, lecture notes, etc.)
CREATE TABLE IF NOT EXISTS digital_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    category VARCHAR(100), -- E-Books, Lecture Notes, etc.
    format VARCHAR(50),   -- PDF, PPT, etc.
    file_path VARCHAR(500) NOT NULL,
    module VARCHAR(100),   -- Subject/Module name
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
