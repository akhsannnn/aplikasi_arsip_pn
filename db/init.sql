-- Pastikan kita menggunakan database yang benar
USE db_arsip;

-- 1. Tabel Users (Untuk Login Admin/Staff)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Password harus di-hash (misal: MD5 atau Bcrypt)
    role ENUM('admin', 'staff') DEFAULT 'staff',
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Categories (Ini berfungsi sebagai "Folder" atau Jenis Dokumen)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL, -- Contoh: "Surat Masuk", "Laporan Keuangan"
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabel Documents (Menyimpan Metadata File)
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL, -- Judul Dokumen
    description TEXT,
    
    -- Informasi File
    file_name VARCHAR(255) NOT NULL, -- Nama file asli (contoh: laporan.pdf)
    file_path VARCHAR(255) NOT NULL, -- Lokasi file di server (contoh: uploads/2026/laporan.pdf)
    file_type VARCHAR(50),           -- Ekstensi file (pdf, docx, jpg)
    file_size INT,                   -- Ukuran file dalam KB
    
    -- Pengelompokan
    category_id INT,
    upload_year YEAR NOT NULL,       -- Penting untuk fitur "Folder Tahunan" kamu
    user_id INT,                     -- Siapa yang mengupload
    
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relasi (Foreign Keys)
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- DUMMY DATA (Data Awal untuk Pengetesan)
-- =============================================

-- Insert User Admin (Password: 'admin123' -> ini contoh raw, nanti di PHP gunakan password_hash)
INSERT INTO users (username, password, role, full_name) VALUES 
('admin', 'admin123', 'admin', 'Administrator Utama'),
('staff', 'staff123', 'staff', 'Staf Arsip');

-- Insert Kategori Dasar
INSERT INTO categories (name, description) VALUES 
('Surat Masuk', 'Dokumen surat yang diterima dari pihak luar'),
('Surat Keluar', 'Arsip surat yang dikirimkan keluar'),
('Laporan Keuangan', 'Dokumen terkait anggaran dan biaya'),
('Dokumen Legal', 'Kontrak, MOU, dan dokumen hukum lainnya');

-- Insert Contoh Dokumen
INSERT INTO documents (title, description, file_name, file_path, file_type, file_size, upload_year, category_id, user_id) VALUES 
('Laporan Januari 2026', 'Laporan bulanan awal tahun', 'laporan_jan.pdf', 'uploads/2026/laporan_jan.pdf', 'pdf', 500, 2026, 3, 1),
('Surat Undangan Rapat', 'Undangan rapat tahunan', 'undangan.docx', 'uploads/2026/undangan.docx', 'docx', 120, 2026, 2, 2);