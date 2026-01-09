-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 09 Jan 2026 pada 00.23
-- Versi server: 8.0.30
-- Versi PHP: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `db_arsip`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `files`
--

CREATE TABLE `files` (
  `id` int NOT NULL,
  `folder_id` int DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `filetype` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `files`
--

INSERT INTO `files` (`id`, `folder_id`, `filename`, `filepath`, `filetype`, `uploaded_at`) VALUES
(5, 14, 'Blanko pi 230210501034 Muh.PDF', 'uploads/2026/1767884995_Blankopi230210501034Muh.PDF', 'pdf', '2026-01-08 15:09:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `folders`
--

CREATE TABLE `folders` (
  `id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `year` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `folders`
--

INSERT INTO `folders` (`id`, `parent_id`, `name`, `description`, `year`, `created_at`) VALUES
(14, NULL, 'Muhammad Akhsan Awaluddin', '', 2026, '2026-01-08 15:09:42');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder_id` (`folder_id`);

--
-- Indeks untuk tabel `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `files`
--
ALTER TABLE `files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
-- --------------------------------------------------------

--
-- Struktur dari tabel `templates`
--

CREATE TABLE `templates` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `year` int DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `templates`
--

INSERT INTO `templates` (`id`, `name`, `description`, `is_default`, `created_at`) VALUES
(1, 'Template Standar Pengadilan', 'Template folder struktur standar untuk pengadilan negeri', 1, CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Struktur dari tabel `template_folders`
--

CREATE TABLE `template_folders` (
  `id` int NOT NULL,
  `template_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `order_index` int DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `template_folders`
--

INSERT INTO `template_folders` (`template_id`, `parent_id`, `name`, `description`, `order_index`) VALUES
(1, NULL, 'PERDATA', 'Dokumen Perkara Perdata', 1),
(1, NULL, 'PIDANA', 'Dokumen Perkara Pidana', 2),
(1, NULL, 'TATA USAHA NEGARA', 'Dokumen TUN', 3),
(1, NULL, 'KOREKSI', 'Dokumen Koreksi Kesalahan', 4),
(1, NULL, 'ADMINISTRASI UMUM', 'Dokumen Administrasi Umum', 5),
(1, 1, 'Gugatan', 'Folder Gugatan Perdata', 1),
(1, 1, 'Keberatan', 'Folder Keberatan', 2),
(1, 1, 'Kuasa Hukum', 'Folder Kuasa Hukum', 3),
(1, 2, 'Penuntutan', 'Folder Penuntutan', 1),
(1, 2, 'Keputusan', 'Folder Keputusan Hakim', 2),
(1, 2, 'Berkala', 'Folder Laporan Berkala', 3);

--
-- Indeks untuk tabel `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `template_folders`
--
ALTER TABLE `template_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- AUTO_INCREMENT untuk tabel `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `template_folders`
--
ALTER TABLE `template_folders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel `template_folders`
--
ALTER TABLE `template_folders`
  ADD CONSTRAINT `template_folders_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
