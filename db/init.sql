-- Pastikan menggunakan database yang benar
USE db_arsip;

-- ==========================================
-- 1. TABEL PENGGUNA (Login)
-- ==========================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` int NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL, -- Password plain text (sesuai request) atau hash
    `role` enum('admin', 'staff') DEFAULT 'staff',
    `full_name` varchar(100),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 2. TABEL FOLDERS (Struktur Arsip)
-- Menggantikan tabel 'categories' yang salah
-- ==========================================
CREATE TABLE IF NOT EXISTS `folders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `year` int NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL, -- Fitur Trash
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. TABEL FILES (Dokumen Upload)
-- Menggantikan tabel 'documents' yang salah
-- ==========================================
CREATE TABLE IF NOT EXISTS `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `folder_id` int DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filetype` varchar(50),
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL, -- Fitur Trash
  PRIMARY KEY (`id`),
  FOREIGN KEY (`folder_id`) REFERENCES `folders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 4. TABEL TEMPLATE (Template Manager)
-- ==========================================
CREATE TABLE IF NOT EXISTS `templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 5. TABEL ITEM TEMPLATE (Struktur Template)
-- ==========================================
CREATE TABLE IF NOT EXISTS `template_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- DUMMY DATA (User Default)
-- ==========================================
INSERT IGNORE INTO `users` (`username`, `password`, `role`, `full_name`) VALUES 
('admin', 'admin123', 'admin', 'Administrator'),
('staff', 'staff123', 'staff', 'Staf Arsip');