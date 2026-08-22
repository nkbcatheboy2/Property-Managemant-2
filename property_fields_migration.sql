-- Run this once on an existing property_management database.
-- New installations should use property_management.sql.
ALTER TABLE properties
    ADD COLUMN scheme_address VARCHAR(255) NULL AFTER scheme_name,
    ADD COLUMN property_type ENUM('Residential','Commercial','Shop','Office','Plot','Flat') NOT NULL DEFAULT 'Residential' AFTER area_size,
    ADD COLUMN allotment_date DATE NULL AFTER property_type;

ALTER TABLE citizen_requests
    MODIFY COLUMN request_type ENUM('Mutation','KYC','NOC','Surrender') NOT NULL;