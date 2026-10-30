-- Attachment Management System - database
-- Import this file once in phpMyAdmin (Import tab) or run it in the MySQL console.

CREATE DATABASE IF NOT EXISTS attachment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attachment_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','supervisor','coordinator','admin') NOT NULL,
  reg_no VARCHAR(50) NULL,
  phone VARCHAR(30) NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  location VARCHAR(150) NULL,
  contact_person VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS placements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  company_id INT NOT NULL,
  supervisor_id INT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student (student_id),
  CONSTRAINT fk_pl_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pl_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
  CONSTRAINT fk_pl_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  placement_id INT NOT NULL,
  att_date DATE NOT NULL,
  time_in DATETIME NOT NULL,
  time_out DATETIME NULL,
  activity TEXT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  supervisor_comment VARCHAR(255) NULL,
  UNIQUE KEY uq_day (placement_id, att_date),
  CONSTRAINT fk_att_placement FOREIGN KEY (placement_id) REFERENCES placements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Demo accounts (all use the password: Pass1234)
-- Change or delete them after your first login.
-- ---------------------------------------------------------------
INSERT IGNORE INTO users (full_name, email, password, role, reg_no) VALUES
('System Admin',      'admin@attach.local',       '$2y$10$kGh.ggZV3UuVmo9QUObcae8rLiPE6.ooNKHY3IEBWAqtyTXek5GaK', 'admin',       NULL),
('Demo Coordinator',  'coordinator@attach.local', '$2y$10$kGh.ggZV3UuVmo9QUObcae8rLiPE6.ooNKHY3IEBWAqtyTXek5GaK', 'coordinator', NULL),
('Demo Supervisor',   'supervisor@attach.local',  '$2y$10$kGh.ggZV3UuVmo9QUObcae8rLiPE6.ooNKHY3IEBWAqtyTXek5GaK', 'supervisor',  NULL),
('Demo Student',      'student@attach.local',     '$2y$10$kGh.ggZV3UuVmo9QUObcae8rLiPE6.ooNKHY3IEBWAqtyTXek5GaK', 'student',     'REG/0001/2026');

INSERT INTO companies (name, location, contact_person, phone)
SELECT 'Demo Company Ltd', 'Nairobi', 'HR Office', '0700000000' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM companies WHERE name = 'Demo Company Ltd');

INSERT INTO placements (student_id, company_id, supervisor_id, start_date, end_date)
SELECT s.id, c.id, v.id, CURDATE() - INTERVAL 14 DAY, CURDATE() - INTERVAL 14 DAY + INTERVAL 12 WEEK
FROM users s, users v, companies c
WHERE s.email = 'student@attach.local' AND v.email = 'supervisor@attach.local' AND c.name = 'Demo Company Ltd'
AND NOT EXISTS (SELECT 1 FROM placements);
