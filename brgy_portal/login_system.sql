CREATE DATABASE IF NOT EXISTS login_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE login_system;

-- Users table (residents and admin)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Document requests table
CREATE TABLE IF NOT EXISTS requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  document_type VARCHAR(50) NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  status ENUM('Pending','Processing','Ready for Pickup','Rejected')
    NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample accounts
-- admin    / Admin@123
-- resident / Resident@123
INSERT INTO users (id, username, password, role) VALUES
(1, 'admin', '$2y$10$xG8Jc/dAI2rMMKQ/jgkNfuEkZx00ULhrvwtvhQ.czliXhuL/xVRb2', 'admin'),
(2, 'resident', '$2y$10$cAuU6FYvMvk6kMhFihyGuO4IDvlIW0zv/Xrpdhax5oXb00oAlx.cW', 'user');

INSERT INTO requests (user_id, document_type, purpose, status) VALUES
(2, 'Barangay Clearance', 'Job application', 'Pending'),
(2, 'Certificate of Indigency', 'Scholarship application', 'Processing'),
(2, 'Certificate of Residency', 'School enrollment', 'Ready for Pickup');
