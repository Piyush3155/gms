-- Gym Management System Database Schema

CREATE DATABASE IF NOT EXISTS gym_management;
USE gym_management;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'trainer', 'member') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Membership plans table
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration_months INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT
);

-- Trainers table
CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    contact VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    experience INT,
    salary DECIMAL(10,2),
    join_date DATE
);

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    dob DATE,
    contact VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    address TEXT,
    join_date DATE,
    plan_id INT,
    trainer_id INT,
    status ENUM('active', 'expired', 'inactive') DEFAULT 'active',
    photo VARCHAR(255),
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('trainer', 'member') NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('present', 'absent') DEFAULT 'present',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    method ENUM('cash', 'card', 'upi', 'bank_transfer') NOT NULL,
    invoice_no VARCHAR(50) UNIQUE,
    status ENUM('paid', 'pending', 'failed') DEFAULT 'paid',
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Workout plans table
CREATE TABLE workout_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Diet plans table
CREATE TABLE diet_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gym_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20),
    address TEXT,
    email VARCHAR(100),
    logo VARCHAR(255)
);

-- Insert default admin user
INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password is 'password' hashed with bcrypt

-- Insert sample plans
INSERT INTO plans (name, duration_months, amount, description) VALUES
('Basic Plan', 1, 500.00, 'Basic membership with access to gym equipment'),
('Premium Plan', 3, 1200.00, 'Premium membership with trainer sessions'),
('VIP Plan', 12, 5000.00, 'VIP membership with personal trainer and diet plans');