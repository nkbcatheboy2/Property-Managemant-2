-- ============================================
-- Property Management System - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS property_management;
USE property_management;

-- -----------------------------
-- Roles Table
-- -----------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (role_name) VALUES
('Admin'),
('Property Officer'),
('LDA'),
('UDC'),
('SO');

-- -----------------------------
-- Users Table
-- -----------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- -----------------------------
-- Default Admin User
-- Isko database me manually insert NAHI karna - iske liye
-- "create_admin.php" file di gayi hai jo browser me chalane par
-- सही bcrypt hash ke saath admin user create kar degi.
-- -----------------------------

-- -----------------------------
-- Login Activity Log (optional but useful)
-- -----------------------------
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- -----------------------------
-- Properties Table
-- -----------------------------
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_title VARCHAR(150) NOT NULL,
    location VARCHAR(200) NOT NULL,
    area_size VARCHAR(50),                 -- e.g. "1200 sq ft"
    price DECIMAL(15,2) NOT NULL,
    category ENUM('Lottery','Auction','FCFS','Direct Allotment') NOT NULL,
    status ENUM('Available','Pending','Sold','Allotted') DEFAULT 'Available',
    description TEXT,
    image VARCHAR(255),                    -- uploaded image filename
    added_by INT NOT NULL,                 -- users.id (kis officer/admin ne add kiya)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id)
);
