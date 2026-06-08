-- ============================================
-- Library Management System Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS library_management_system;
USE library_management_system;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'librarian', 'student') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Categories Table
-- ============================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Books Table
-- ============================================
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(20) UNIQUE NOT NULL,
    publisher VARCHAR(150),
    publication_year YEAR,
    quantity INT NOT NULL DEFAULT 0,
    available_quantity INT NOT NULL DEFAULT 0,
    book_cover VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    CHECK (quantity >= 0),
    CHECK (available_quantity >= 0),
    CHECK (available_quantity <= quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Students Table
-- ============================================
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    year VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Borrow Records Table
-- ============================================
CREATE TABLE borrow_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Fines Table
-- ============================================
CREATE TABLE fines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    borrow_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT,
    paid_status ENUM('paid', 'unpaid', 'partial') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (borrow_id) REFERENCES borrow_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Notifications Table
-- ============================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- System Settings Table
-- ============================================
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default Settings
-- ============================================
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('fine_per_day', '1.00', 'Fine amount per overdue day in USD'),
('max_borrow_days', '14', 'Maximum days a book can be borrowed'),
('max_books_per_student', '5', 'Maximum books a student can borrow at once'),
('system_name', 'Library Management System', 'System name'),
('system_email', 'library@example.com', 'System email address');

-- ============================================
-- Insert Default Categories
-- ============================================
INSERT INTO categories (category_name, description) VALUES
('Fiction', 'Fiction books including novels and short stories'),
('Non-Fiction', 'Non-fiction books including biographies and essays'),
('Science', 'Science and technology related books'),
('Technology', 'Computer science and IT books'),
('History', 'Historical books and documentation'),
('Mathematics', 'Mathematics and statistics books'),
('Literature', 'Classic and modern literature'),
('Business', 'Business and economics books'),
('Arts', 'Arts and design books'),
('Reference', 'Reference books and encyclopedias');

-- ============================================
-- Insert Default Super Admin
-- Password: admin123
-- ============================================
INSERT INTO users (full_name, email, username, password, role, status) VALUES
('Super Administrator', 'admin@library.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active');

-- ============================================
-- Insert Sample Librarian
-- Password: librarian123
-- ============================================
INSERT INTO users (full_name, email, username, password, role, status) VALUES
('John Librarian', 'librarian@library.com', 'librarian', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', 'active');

-- ============================================
-- Insert Sample Student User
-- Password: student123
-- ============================================
INSERT INTO users (full_name, email, username, password, role, status) VALUES
('Jane Student', 'student@library.com', 'student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active');

-- ============================================
-- Insert Sample Student Record
-- ============================================
INSERT INTO students (user_id, student_id, full_name, department, year, phone, email) VALUES
(3, 'STU001', 'Jane Student', 'Computer Science', 'Year 3', '555-0123', 'student@library.com');

-- ============================================
-- Insert Sample Books
-- ============================================
INSERT INTO books (category_id, book_title, author, isbn, publisher, publication_year, quantity, available_quantity, description) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', '978-0-7432-7356-5', 'Scribner', 2004, 5, 5, 'A classic American novel set in the Jazz Age'),
(4, 'Clean Code', 'Robert C. Martin', '978-0-13-235088-4', 'Prentice Hall', 2008, 10, 10, 'A handbook of agile software craftsmanship'),
(3, 'A Brief History of Time', 'Stephen Hawking', '978-0-553-38016-3', 'Bantam', 1998, 7, 7, 'From the Big Bang to Black Holes'),
(4, 'The Pragmatic Programmer', 'Andrew Hunt', '978-0-201-61622-4', 'Addison-Wesley', 1999, 8, 8, 'Your journey to mastery'),
(7, 'To Kill a Mockingbird', 'Harper Lee', '978-0-06-112008-4', 'Harper Perennial', 2006, 6, 6, 'A gripping tale of racial injustice and childhood innocence');

-- ============================================
-- Create Indexes for Performance
-- ============================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_books_isbn ON books(isbn);
CREATE INDEX idx_books_category ON books(category_id);
CREATE INDEX idx_students_student_id ON students(student_id);
CREATE INDEX idx_borrow_status ON borrow_records(status);
CREATE INDEX idx_borrow_dates ON borrow_records(borrow_date, due_date);
CREATE INDEX idx_notifications_user ON notifications(user_id, status);

-- ============================================
-- Create Views for Common Queries
-- ============================================

-- View for available books
CREATE VIEW available_books_view AS
SELECT 
    b.id,
    b.book_title,
    b.author,
    b.isbn,
    b.publisher,
    b.publication_year,
    b.quantity,
    b.available_quantity,
    b.book_cover,
    c.category_name
FROM books b
INNER JOIN categories c ON b.category_id = c.id
WHERE b.available_quantity > 0;

-- View for active borrows
CREATE VIEW active_borrows_view AS
SELECT 
    br.id,
    br.borrow_date,
    br.due_date,
    br.status,
    br.fine_amount,
    s.student_id,
    s.full_name AS student_name,
    s.email AS student_email,
    b.book_title,
    b.author,
    b.isbn,
    DATEDIFF(CURDATE(), br.due_date) AS overdue_days
FROM borrow_records br
INNER JOIN students s ON br.student_id = s.id
INNER JOIN books b ON br.book_id = b.id
WHERE br.status IN ('borrowed', 'overdue');

-- View for overdue books
CREATE VIEW overdue_books_view AS
SELECT 
    br.id,
    br.borrow_date,
    br.due_date,
    s.student_id,
    s.full_name AS student_name,
    s.email AS student_email,
    s.phone,
    b.book_title,
    b.author,
    DATEDIFF(CURDATE(), br.due_date) AS overdue_days,
    DATEDIFF(CURDATE(), br.due_date) * 1.00 AS calculated_fine
FROM borrow_records br
INNER JOIN students s ON br.student_id = s.id
INNER JOIN books b ON br.book_id = b.id
WHERE br.status = 'borrowed' 
AND br.due_date < CURDATE();

-- ============================================
-- Triggers
-- ============================================

-- Trigger to update book availability when borrowed
DELIMITER $$
CREATE TRIGGER after_borrow_insert
AFTER INSERT ON borrow_records
FOR EACH ROW
BEGIN
    IF NEW.status = 'borrowed' THEN
        UPDATE books 
        SET available_quantity = available_quantity - 1 
        WHERE id = NEW.book_id AND available_quantity > 0;
    END IF;
END$$

-- Trigger to update book availability when returned
CREATE TRIGGER after_borrow_update
AFTER UPDATE ON borrow_records
FOR EACH ROW
BEGIN
    IF OLD.status = 'borrowed' AND NEW.status = 'returned' THEN
        UPDATE books 
        SET available_quantity = available_quantity + 1 
        WHERE id = NEW.book_id;
    END IF;
END$$

-- Trigger to update overdue status
CREATE TRIGGER before_borrow_update_overdue
BEFORE UPDATE ON borrow_records
FOR EACH ROW
BEGIN
    IF NEW.status = 'borrowed' AND NEW.due_date < CURDATE() THEN
        SET NEW.status = 'overdue';
    END IF;
END$$

DELIMITER ;

-- ============================================
-- End of Database Schema
-- ============================================
