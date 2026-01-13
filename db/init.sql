USE db_arsip;

-- ==========================================
-- 1. BERSIH-BERSIH (DROP TABLE)
-- ==========================================
DROP TABLE IF EXISTS `files`;
DROP TABLE IF EXISTS `folders`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `template_items`;
DROP TABLE IF EXISTS `templates`;

-- ==========================================
-- 2. TABEL PENGGUNA (Login)
-- ==========================================
CREATE TABLE `users` (
    `id` int NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL, -- Password Plain Text
    `role` enum('admin', 'staff') DEFAULT 'staff',
    `full_name` varchar(100),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. TABEL FOLDERS (Arsip Tahunan)
-- ==========================================
CREATE TABLE `folders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `year` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`deleted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 4. TABEL FILES (Dokumen)
-- ==========================================
CREATE TABLE `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `folder_id` int DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filetype` varchar(50),
  `user_id` int DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`folder_id`) REFERENCES `folders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`deleted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 5. TABEL TEMPLATE
-- ==========================================
CREATE TABLE `templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 6. TABEL ITEM TEMPLATE
-- ==========================================
CREATE TABLE `template_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- DATA: USER LOGIN (Plain Text)
-- ==========================================
INSERT INTO `users` (`username`, `password`, `role`, `full_name`) VALUES 
('admin', 'admin123', 'admin', 'Administrator'),
('staff', 'staff123', 'staff', 'Staf Arsip');

-- ==========================================
-- DATA: TEMPLATE STANDAR
-- ==========================================
INSERT INTO `templates` (`id`, `name`, `description`) VALUES
(1, 'Template Standar Pengadilan', 'Struktur folder standar untuk pengadilan negeri');

-- Folder Utama (Parent NULL)
INSERT INTO `template_items` (`id`, `template_id`, `parent_id`, `name`, `description`) VALUES
(1, 1, NULL, 'PERDATA', 'Dokumen Perkara Perdata'),
(2, 1, NULL, 'PIDANA', 'Dokumen Perkara Pidana'),
(3, 1, NULL, 'TATA USAHA NEGARA', 'Dokumen TUN'),
(4, 1, NULL, 'KOREKSI', 'Dokumen Koreksi Kesalahan'),
(5, 1, NULL, 'ADMINISTRASI UMUM', 'Dokumen Administrasi Umum');

-- Subfolder PERDATA
INSERT INTO `template_items` (`template_id`, `parent_id`, `name`, `description`) VALUES
(1, 1, 'Gugatan', 'Folder Gugatan'),
(1, 1, 'Keberatan', 'Folder Keberatan'),
(1, 1, 'Kuasa Hukum', 'Folder Kuasa Hukum');

-- Subfolder PIDANA
INSERT INTO `template_items` (`template_id`, `parent_id`, `name`, `description`) VALUES
(1, 2, 'Penuntutan', 'Folder Penuntutan'),
(1, 2, 'Keputusan', 'Folder Keputusan Hakim'),
(1, 2, 'Berkala', 'Folder Laporan Berkala');