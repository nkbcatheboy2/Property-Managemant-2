-- Run this ONLY if you already imported database.sql earlier
-- and just need to add the new "properties" table.

USE property_management;

CREATE TABLE IF NOT EXISTS properties (
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
