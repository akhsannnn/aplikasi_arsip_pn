-- Insert default template folders
INSERT IGNORE INTO template_folders (template_id, parent_id, name, description, order_index) VALUES
(1, NULL, 'PERDATA', 'Dokumen Perkara Perdata', 1),
(1, NULL, 'PIDANA', 'Dokumen Perkara Pidana', 2),
(1, NULL, 'TATA USAHA NEGARA', 'Dokumen TUN', 3),
(1, NULL, 'KOREKSI', 'Dokumen Koreksi Kesalahan', 4),
(1, NULL, 'ADMINISTRASI UMUM', 'Dokumen Administrasi Umum', 5);
