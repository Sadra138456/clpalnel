-- Veterinary Clinic Database Schema
-- Run this in your MySQL database (phpMyAdmin or command line)

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    google_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_google_id (google_id)
);

-- Reservations table  
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    pet_type VARCHAR(50) NOT NULL,
    pet_age INT,
    owner_name VARCHAR(100) NOT NULL,
    owner_phone VARCHAR(20) NOT NULL,
    owner_email VARCHAR(255),
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    vaccine_type VARCHAR(100),
    notes TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    price DECIMAL(10, 2) DEFAULT 0.00,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (reservation_date),
    INDEX idx_status (status)
);

-- SMS Archive table
CREATE TABLE sms_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    provider VARCHAR(50) DEFAULT 'kavenegar',
    message_id VARCHAR(100),
    status ENUM('sent', 'delivered', 'failed') DEFAULT 'sent',
    cost DECIMAL(8, 4) DEFAULT 0.0000,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reservation_id INT,
    message_type ENUM('reservation', 'reminder', 'notification', 'custom') DEFAULT 'custom',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_phone (recipient_phone),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password, first_name, last_name, role, is_active) 
VALUES ('admin@vet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدیر', 'سیستم', 'admin', TRUE);

-- Insert sample data
INSERT INTO reservations (user_id, pet_name, pet_type, pet_age, owner_name, owner_phone, owner_email, reservation_date, reservation_time, service_type, vaccine_type, notes, status, price) 
VALUES 
(1, 'پشمی', 'گربه', 2, 'علی احمدی', '09123456789', 'ali@example.com', '2024-01-15', '10:00:00', 'واکسیناسیون', 'واکسن سه‌گانه', 'گربه آرام و مطیع', 'pending', 150000.00),
(1, 'بابی', 'سگ', 1, 'مریم رضایی', '09987654321', 'maryam@example.com', '2024-01-16', '14:30:00', 'معاینه عمومی', NULL, 'سگ پرانرژی', 'confirmed', 100000.00);