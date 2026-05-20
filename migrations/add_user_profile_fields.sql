ALTER TABLE users 
ADD COLUMN email VARCHAR(150) NULL AFTER name,
ADD COLUMN address_street VARCHAR(255) NULL AFTER avatar,
ADD COLUMN address_number VARCHAR(20) NULL AFTER address_street,
ADD COLUMN address_neighborhood VARCHAR(100) NULL AFTER address_number,
ADD COLUMN birth_date DATE NULL AFTER address_neighborhood;
