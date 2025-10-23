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
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gym_name VARCHAR(100) NOT NULL,
    tagline VARCHAR(255),
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

-- Insert default gym settings
INSERT INTO settings (gym_name, tagline, contact, address, email) VALUES
('FitZone Gym', 'Transform Your Body, Transform Your Life', '+1-555-0123', '123 Fitness Street, Health City, HC 12345', 'info@fitzone.com');

-- More dummy data for testing

-- Trainers
INSERT INTO trainers (name, specialization, contact, email, experience, salary, join_date) VALUES
('John Doe', 'Strength Training', '+1-555-1001', 'john.doe@fitzone.com', 5, 3000.00, '2022-01-10'),
('Jane Smith', 'Yoga', '+1-555-1002', 'jane.smith@fitzone.com', 3, 2500.00, '2023-03-15'),
('Mike Lee', 'Cardio', '+1-555-1003', 'mike.lee@fitzone.com', 4, 2800.00, '2021-07-20');

-- Members
INSERT INTO members (name, gender, dob, contact, email, address, join_date, plan_id, trainer_id, status, photo) VALUES
('Alice Brown', 'female', '1995-05-12', '+1-555-2001', 'alice.brown@example.com', '456 Wellness Ave, Health City', '2024-01-05', 1, 1, 'active', 'alice.jpg'),
('Bob Green', 'male', '1990-08-22', '+1-555-2002', 'bob.green@example.com', '789 Power St, Health City', '2024-02-10', 2, 2, 'active', 'bob.jpg'),
('Charlie Black', 'male', '1988-11-30', '+1-555-2003', 'charlie.black@example.com', '321 Energy Rd, Health City', '2024-03-15', 3, 3, 'expired', 'charlie.jpg');

-- Payments
INSERT INTO payments (member_id, plan_id, amount, payment_date, method, invoice_no, status) VALUES
(1, 1, 500.00, '2024-01-05', 'cash', 'INV1001', 'paid'),
(2, 2, 1200.00, '2024-02-10', 'card', 'INV1002', 'paid'),
(3, 3, 5000.00, '2024-03-15', 'upi', 'INV1003', 'pending');

-- Attendance
INSERT INTO attendance (user_id, role, date, check_in, check_out, status) VALUES
(2, 'member', '2024-10-20', '08:00:00', '10:00:00', 'present'),
(3, 'member', '2024-10-20', '09:00:00', '11:00:00', 'present'),
(1, 'trainer', '2024-10-20', '07:30:00', '12:00:00', 'present');

-- Workout Plans
INSERT INTO workout_plans (trainer_id, member_id, description) VALUES
(1, 1, 'Strength training: Monday, Wednesday, Friday'),
(2, 2, 'Yoga and flexibility: Tuesday, Thursday'),
(3, 3, 'Cardio: Daily morning sessions');

-- Diet Plans
INSERT INTO diet_plans (trainer_id, member_id, description) VALUES
(1, 1, 'High protein, low carb diet'),
(2, 2, 'Vegetarian diet with supplements'),
(3, 3, 'Balanced diet with focus on hydration');

-- Expenses
INSERT INTO expenses (category, amount, expense_date, description) VALUES
('Equipment', 1500.00, '2024-09-01', 'New dumbbells and weights'),
('Maintenance', 500.00, '2024-09-10', 'AC repair'),
('Marketing', 800.00, '2024-09-15', 'Social media ads');