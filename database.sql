
CREATE DATABASE IF NOT EXISTS property_management;
USE property_management;


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


CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id)
);


CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_title VARCHAR(150) NOT NULL,
    location VARCHAR(200) NOT NULL,
    area_size VARCHAR(50),                
    price DECIMAL(15,2) NOT NULL,
    category ENUM('Lottery','Auction','FCFS','Direct Allotment') NOT NULL,
    status ENUM('Available','Pending','Sold','Allotted') DEFAULT 'Available',
    description TEXT,
    image VARCHAR(255),                  
    added_by INT NOT NULL,                 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id)
);
