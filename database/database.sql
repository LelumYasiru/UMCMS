-- Database: my_new_db
CREATE DATABASE IF NOT EXISTS my_new_db;
USE my_new_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'pharmacist', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reg_number VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone_number VARCHAR(20) DEFAULT NULL,
    gender VARCHAR(10) DEFAULT NULL,
    blood_type VARCHAR(5) DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    medical_report VARCHAR(255) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.png',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Medicines Table
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    stock_quantity INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- 4. Prescriptions Table
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    doctor_id INT NOT NULL,
    notes TEXT,
    status ENUM('pending', 'dispensed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Prescription Items Table
CREATE TABLE IF NOT EXISTS prescription_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Admin Messages (from Pharmacist)
CREATE TABLE IF NOT EXISTS admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed Data (Initial Admin Account)
-- Password: password123
INSERT IGNORE INTO users (username, password, role) VALUES 
('admin', '$2y$10$95.vFpQ3U6V8u3H6W1j7OeN5ZcQ7n3Z6V7h/f1vQ8h/d7D1s3q5X.', 'admin');

-- Seed Data (Initial Medicines)
INSERT IGNORE INTO medicines (name, stock_quantity) VALUES 
('Paracetamol', 500),
('Amoxicillin', 200),
('Cetirizine', 300),
('Ibuprofen', 150),
('Salbutamol', 100);
