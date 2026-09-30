USE property_management;
ALTER TABLE citizen_requests MODIFY service_type ENUM('Mutation','KYC Update') NOT NULL;
