-- Rodar este comando no SQL do phpMyAdmin para atualizar a tabela
ALTER TABLE users 
ADD COLUMN address_street VARCHAR(150) AFTER phone,
ADD COLUMN address_number VARCHAR(20) AFTER address_street,
ADD COLUMN address_neighborhood VARCHAR(100) AFTER address_number,
ADD COLUMN avatar VARCHAR(255) AFTER address_neighborhood;
