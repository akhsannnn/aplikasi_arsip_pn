-- Tambahan Tabel untuk Template Management
-- Jalankan SQL ini di database Anda

-- ========================================
-- TABEL BARU: templates
-- ========================================
CREATE TABLE `templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `year` int,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ========================================
-- TABEL BARU: template_folders
-- Menyimpan struktur folder untuk setiap template
-- ========================================
CREATE TABLE `template_folders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `order_index` int DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ========================================
-- INSERT TEMPLATE DEFAULT (Contoh)
-- ========================================
INSERT INTO `templates` (`name`, `description`, `is_default`) VALUES
('Template Standar Pengadilan', 'Template folder struktur standar untuk pengadilan negeri', 1);

-- ========================================
-- INSERT FOLDER-FOLDER DEFAULT
-- ========================================
INSERT INTO `template_folders` (`template_id`, `parent_id`, `name`, `description`, `order_index`) VALUES
(1, NULL, 'PERDATA', 'Dokumen Perkara Perdata', 1),
(1, NULL, 'PIDANA', 'Dokumen Perkara Pidana', 2),
(1, NULL, 'TATA USAHA NEGARA', 'Dokumen TUN', 3),
(1, NULL, 'KOREKSI', 'Dokumen Koreksi Kesalahan', 4),
(1, NULL, 'ADMINISTRASI UMUM', 'Dokumen Administrasi Umum', 5);

-- Subfolder di bawah PERDATA
INSERT INTO `template_folders` (`template_id`, `parent_id`, `name`, `description`, `order_index`) VALUES
(1, 1, 'Gugatan', 'Folder Gugatan Perdata', 1),
(1, 1, 'Keberatan', 'Folder Keberatan', 2),
(1, 1, 'Kuasa Hukum', 'Folder Kuasa Hukum', 3);

-- Subfolder di bawah PIDANA
INSERT INTO `template_folders` (`template_id`, `parent_id`, `name`, `description`, `order_index`) VALUES
(1, 2, 'Penuntutan', 'Folder Penuntutan', 1),
(1, 2, 'Keputusan', 'Folder Keputusan Hakim', 2),
(1, 2, 'Berkala', 'Folder Laporan Berkala', 3);
