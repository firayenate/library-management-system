USE librinet_db;

CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(student_id)
);

CREATE TABLE IF NOT EXISTS book_holds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    book_id INT NOT NULL,
    status ENUM('In Queue', 'Ready', 'Cancelled') DEFAULT 'In Queue',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(student_id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);
