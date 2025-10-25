-- Gym Management System Database Schema

CREATE DATABASE IF NOT EXISTS gym_management;
USE gym_management;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT DEFAULT 3, -- Default to member
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
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
    expiry_date DATE,
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

-- Equipment table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    purchase_date DATE,
    purchase_cost DECIMAL(10,2),
    location VARCHAR(100),
    status ENUM('available', 'in_use', 'maintenance', 'out_of_order') DEFAULT 'available',
    description TEXT,
    maintenance_schedule VARCHAR(100),
    last_maintenance DATE,
    next_maintenance DATE
);

-- Member progress tracking table
CREATE TABLE member_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    measurement_date DATE NOT NULL,
    weight DECIMAL(5,2),
    height DECIMAL(5,2),
    chest DECIMAL(5,2),
    waist DECIMAL(5,2),
    hips DECIMAL(5,2),
    biceps DECIMAL(5,2),
    thighs DECIMAL(5,2),
    notes TEXT,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Group classes table
CREATE TABLE group_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    trainer_id INT,
    description TEXT,
    capacity INT NOT NULL,
    duration_minutes INT NOT NULL,
    class_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);

-- Class bookings table
CREATE TABLE class_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    member_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled', 'attended') DEFAULT 'confirmed',
    FOREIGN KEY (class_id) REFERENCES group_classes(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking (class_id, member_id)
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

-- Roles table for RBAC
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Permissions table
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL, -- view, add, edit, delete
    UNIQUE KEY unique_permission (module, action)
);

-- Role permissions table
CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
);

-- Insert default admin user
INSERT INTO users (name, email, password, role_id) VALUES ('Admin', 'admin@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
-- Password is 'password' hashed with bcrypt

-- Insert sample trainer user
INSERT INTO users (name, email, password, role_id, phone) VALUES ('Trainer One', 'trainer1@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '+1-555-1001');

-- Insert sample member user
INSERT INTO users (name, email, password, role_id, phone) VALUES ('Member One', 'member1@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '+1-555-2001');

-- Insert sample plans
INSERT INTO plans (name, duration_months, amount, description) VALUES
('Basic Plan', 1, 500.00, 'Basic membership with access to gym equipment'),
('Premium Plan', 3, 1200.00, 'Premium membership with trainer sessions'),
('VIP Plan', 12, 5000.00, 'VIP membership with personal trainer and diet plans');

-- Insert default gym settings
INSERT INTO settings (gym_name, tagline, contact, address, email) VALUES
('FitZone Gym', 'Transform Your Body, Transform Your Life', '+1-555-0123', '123 Fitness Street, Health City, HC 12345', 'info@fitzone.com');

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('Admin', 'Full system access with all permissions'),
('Trainer', 'Manage assigned members, attendance, and plans'),
('Member', 'Access personal profile, attendance, and plans');

-- Insert permissions
INSERT INTO permissions (module, action) VALUES
('dashboard', 'view'),
('members', 'view'),
('members', 'add'),
('members', 'edit'),
('members', 'delete'),
('trainers', 'view'),
('trainers', 'add'),
('trainers', 'edit'),
('trainers', 'delete'),
('plans', 'view'),
('plans', 'add'),
('plans', 'edit'),
('plans', 'delete'),
('attendance', 'view'),
('attendance', 'add'),
('attendance', 'edit'),
('attendance', 'delete'),
('payments', 'view'),
('payments', 'add'),
('payments', 'edit'),
('payments', 'delete'),
('expenses', 'view'),
('expenses', 'add'),
('expenses', 'edit'),
('expenses', 'delete'),
('equipment', 'view'),
('equipment', 'add'),
('equipment', 'edit'),
('equipment', 'delete'),
('member_progress', 'view'),
('member_progress', 'add'),
('member_progress', 'edit'),
('member_progress', 'delete'),
('group_classes', 'view'),
('group_classes', 'add'),
('group_classes', 'edit'),
('group_classes', 'delete'),
('notifications', 'view'),
('notifications', 'add'),
('notifications', 'edit'),
('notifications', 'delete'),
('reports', 'view'),
('settings', 'view'),
('settings', 'edit'),
('profile', 'view'),
('profile', 'edit'),
('rbac', 'view'),
('rbac', 'add'),
('rbac', 'edit'),
('rbac', 'delete'),
('reception', 'view'),
('reception', 'add'),
('reception', 'edit'),
('inventory', 'view'),
('inventory', 'add'),
('inventory', 'edit'),
('inventory', 'delete'),
('suppliers', 'view'),
('suppliers', 'add'),
('suppliers', 'edit'),
('suppliers', 'delete'),
('sales', 'view'),
('sales', 'add'),
('sales', 'edit'),
('sales', 'delete'),
('payroll', 'view'),
('payroll', 'add'),
('payroll', 'edit'),
('payroll', 'delete'),
('feedback', 'view'),
('feedback', 'add'),
('feedback', 'edit'),
('feedback', 'delete'),
('activity_log', 'view'),
('backup', 'view'),
('backup', 'add'),
('branches', 'view'),
('branches', 'add'),
('branches', 'edit'),
('branches', 'delete'),
('api', 'view'),
('api', 'edit');

-- Assign permissions to roles
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions; -- Admin all

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p WHERE p.module IN ('dashboard', 'members', 'attendance', 'workout_plans', 'diet_plans', 'profile') AND p.action IN ('view', 'add', 'edit');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p WHERE p.module IN ('dashboard', 'attendance', 'workout_plans', 'diet_plans', 'classes', 'profile') AND p.action = 'view';

-- Inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2),
    supplier_id INT,
    purchase_date DATE,
    expiry_date DATE,
    description TEXT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT
);

-- Sales table
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    sale_date DATE NOT NULL,
    customer_name VARCHAR(100),
    payment_method ENUM('cash', 'card', 'upi') DEFAULT 'cash',
    FOREIGN KEY (item_id) REFERENCES inventory(id)
);

-- Working hours table
CREATE TABLE working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    hours_worked DECIMAL(4,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payroll table
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month YEAR NOT NULL,
    year YEAR NOT NULL,
    base_salary DECIMAL(10,2),
    hours_worked DECIMAL(5,2),
    overtime_hours DECIMAL(5,2),
    overtime_rate DECIMAL(10,2),
    deductions DECIMAL(10,2),
    net_salary DECIMAL(10,2),
    payment_date DATE,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('feedback', 'complaint') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    rating INT CHECK (rating >=1 AND rating <=5),
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity log table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(50),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Branches table
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(191) UNIQUE NOT NULL,  -- reduced from 255 to 191
    name VARCHAR(100),
    permissions TEXT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- More dummy data for testing

-- Trainers
INSERT INTO trainers (name, specialization, contact, email, experience, salary, join_date) VALUES
('John Doe', 'Strength Training', '+1-555-1001', 'john.doe@fitzone.com', 5, 3000.00, '2022-01-10'),
('Jane Smith', 'Yoga', '+1-555-1002', 'jane.smith@fitzone.com', 3, 2500.00, '2023-03-15'),
('Mike Lee', 'Cardio', '+1-555-1003', 'mike.lee@fitzone.com', 4, 2800.00, '2021-07-20');

-- Members
INSERT INTO members (name, gender, dob, contact, email, address, join_date, expiry_date, plan_id, trainer_id, status, photo) VALUES
('Alice Brown', 'female', '1995-05-12', '+1-555-2001', 'alice.brown@example.com', '456 Wellness Ave, Health City', '2024-01-05', '2024-02-05', 1, 1, 'active', 'alice.jpg'),
('Bob Green', 'male', '1990-08-22', '+1-555-2002', 'bob.green@example.com', '789 Power St, Health City', '2024-02-10', '2024-05-10', 2, 2, 'active', 'bob.jpg'),
('Charlie Black', 'male', '1988-11-30', '+1-555-2003', 'charlie.black@example.com', '321 Energy Rd, Health City', '2024-03-15', '2025-03-15', 3, 3, 'expired', 'charlie.jpg');

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

-- Equipment
INSERT INTO equipment (name, category, quantity, purchase_date, purchase_cost, location, status, description, maintenance_schedule, last_maintenance, next_maintenance) VALUES
('Treadmill', 'Cardio', 5, '2023-01-15', 25000.00, 'Cardio Section', 'available', 'Commercial treadmills with heart rate monitors', 'Monthly', '2024-09-01', '2024-10-01'),
('Dumbbells Set', 'Strength', 20, '2023-03-20', 15000.00, 'Weight Section', 'available', 'Complete set from 5kg to 50kg', 'Weekly', '2024-10-20', '2024-10-27'),
('Bench Press', 'Strength', 3, '2022-11-10', 30000.00, 'Weight Section', 'maintenance', 'Olympic bench press stations', 'Bi-weekly', '2024-10-15', '2024-10-29'),
('Yoga Mats', 'Accessories', 50, '2024-01-05', 2500.00, 'Yoga Studio', 'available', 'Non-slip yoga mats', 'Monthly', '2024-09-15', '2024-10-15'),
('Stationary Bike', 'Cardio', 8, '2023-06-12', 40000.00, 'Cardio Section', 'available', 'Spin bikes with digital displays', 'Weekly', '2024-10-18', '2024-10-25');

-- Member Progress
INSERT INTO member_progress (member_id, measurement_date, weight, height, chest, waist, hips, biceps, thighs, notes) VALUES
(1, '2024-01-05', 70.5, 165.0, 95.0, 80.0, 100.0, 30.0, 55.0, 'Initial measurements'),
(1, '2024-02-05', 69.2, 165.0, 93.5, 78.5, 98.5, 31.2, 56.2, 'Good progress after 1 month'),
(2, '2024-02-10', 85.0, 175.0, 105.0, 90.0, 105.0, 35.0, 60.0, 'Starting measurements'),
(3, '2024-03-15', 78.0, 170.0, 98.0, 85.0, 102.0, 32.0, 58.0, 'Initial assessment');

-- Group Classes
INSERT INTO group_classes (name, trainer_id, description, capacity, duration_minutes, class_date, start_time, end_time, status) VALUES
('Morning Yoga', 2, 'Relaxing yoga session for all levels', 20, 60, '2024-10-26', '07:00:00', '08:00:00', 'scheduled'),
('Strength Training', 1, 'Full body strength workout', 15, 90, '2024-10-26', '18:00:00', '19:30:00', 'scheduled'),
('Cardio Blast', 3, 'High intensity cardio session', 25, 45, '2024-10-27', '19:00:00', '19:45:00', 'scheduled'),
('Pilates Core', 2, 'Core strengthening with Pilates', 12, 60, '2024-10-28', '10:00:00', '11:00:00', 'scheduled');

-- Class Bookings
INSERT INTO class_bookings (class_id, member_id, status) VALUES
(1, 1, 'confirmed'),
(1, 2, 'confirmed'),
(2, 1, 'confirmed'),
(2, 3, 'attended'),
(3, 2, 'confirmed');

-- Sample suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('Fitness Supplies Inc', 'John Supplier', '+1-555-3001', 'john@fitnesssupplies.com', '100 Supply St, Supplier City'),
('Health Gear Ltd', 'Jane Gear', '+1-555-3002', 'jane@healthgear.com', '200 Gear Ave, Gear Town');

-- Sample inventory
INSERT INTO inventory (name, category, quantity, unit_price, supplier_id, purchase_date, expiry_date, description) VALUES
('Protein Powder', 'Supplements', 50, 25.00, 1, '2024-09-01', '2025-09-01', 'Whey protein powder 1kg'),
('Resistance Bands', 'Equipment', 20, 15.00, 2, '2024-08-15', NULL, 'Set of 5 resistance bands'),
('Energy Drinks', 'Beverages', 100, 2.50, 1, '2024-10-01', '2025-04-01', 'Caffeine energy drink 500ml');

-- Sample sales
INSERT INTO sales (item_id, quantity, unit_price, total_amount, sale_date, customer_name, payment_method) VALUES
(1, 2, 25.00, 50.00, '2024-10-20', 'Walk-in Customer', 'cash'),
(3, 5, 2.50, 12.50, '2024-10-21', 'Member Alice', 'card');

-- Sample working hours
INSERT INTO working_hours (user_id, date, hours_worked) VALUES
(2, '2024-10-20', 8.00),
(2, '2024-10-21', 7.50);

-- Sample payroll
INSERT INTO payroll (user_id, month, year, base_salary, hours_worked, net_salary, status) VALUES
(2, 10, 2024, 3000.00, 15.50, 3000.00, 'pending');

-- Sample feedback
INSERT INTO feedback (user_id, type, subject, message, rating, status) VALUES
(3, 'feedback', 'Great service', 'The trainers are very helpful', 5, 'reviewed'),
(3, 'complaint', 'Equipment issue', 'Treadmill was not working', NULL, 'pending');

-- Sample activity log
INSERT INTO activity_log (user_id, action, module, details) VALUES
(1, 'Login', 'auth', 'Admin logged in'),
(2, 'Marked attendance', 'attendance', 'Marked attendance for member 1');

-- Sample branch
INSERT INTO branches (name, address, phone, email, manager_id) VALUES
('Main Branch', '123 Fitness Street, Health City', '+1-555-0123', 'main@fitzone.com', 1);

-- Sample API key
INSERT INTO api_keys (user_id, api_key, name, permissions) VALUES
(1, 'api_key_sample_12345', 'Admin API Key', '{"members":"read","attendance":"read"}');