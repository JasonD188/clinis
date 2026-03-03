-- BCP Clinic Management System - Database Setup
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS bcp_clinic;
USE bcp_clinic;

-- Table: Patients
CREATE TABLE IF NOT EXISTS patients (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    dob DATE,
    gender VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    emergency_name VARCHAR(100),
    emergency_phone VARCHAR(20),
    blood_type VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: Appointments
CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11),
    doctor_name VARCHAR(100),
    appointment_date DATE,
    appointment_time TIME,
    type ENUM('Consultation', 'Check-up', 'Emergency', 'Phone Booking') DEFAULT 'Consultation',
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Table: Medical Records
CREATE TABLE IF NOT EXISTS medical_records (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11),
    bp VARCHAR(20),
    weight VARCHAR(10),
    height VARCHAR(10),
    temperature VARCHAR(10),
    diagnosis TEXT,
    prescription TEXT,
    consultation_notes TEXT,
    record_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Table: Inventory
CREATE TABLE IF NOT EXISTS inventory (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100),
    category ENUM('Medicine', 'Equipment', 'Supply'),
    quantity INT(11),
    unit VARCHAR(20),
    status ENUM('In Stock', 'Low Stock', 'Out of Stock') DEFAULT 'In Stock',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: Rooms/Equipment
CREATE TABLE IF NOT EXISTS rooms (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100),
    room_type ENUM('Consultation Room', 'Treatment Room', 'Waiting Area', 'Storage') DEFAULT 'Consultation Room',
    status ENUM('Available', 'Occupied', 'Under Maintenance') DEFAULT 'Available',
    notes TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: Equipment Maintenance
CREATE TABLE IF NOT EXISTS maintenance (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(100),
    schedule_date DATE,
    technician VARCHAR(100),
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: Users (Staff/Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'nurse', 'doctor') DEFAULT 'nurse',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Inventory REPORTS --
CREATE TABLE inventory_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    action_type ENUM('ADD','REMOVE','RESTOCK') NOT NULL,
    quantity INT NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
);
-- Default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE username=username;

-- Sample Rooms
INSERT INTO rooms (room_name, room_type, status) VALUES
('Room 1', 'Consultation Room', 'Available'),
('Room 2', 'Consultation Room', 'Available'),
('Treatment Room A', 'Treatment Room', 'Available'),
('Waiting Area', 'Waiting Area', 'Available')
ON DUPLICATE KEY UPDATE room_name=room_name;
