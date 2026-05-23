USE librinet_db;

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    isbn VARCHAR(100),
    category VARCHAR(100),
    copies INT DEFAULT 1,
    status ENUM('Available', 'Borrowed') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS issued_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending', 'Returned', 'Overdue') DEFAULT 'Pending',
    FOREIGN KEY (student_id) REFERENCES users(student_id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);
