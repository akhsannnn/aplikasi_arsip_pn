# 📋 TEMPLATE MANAGEMENT SYSTEM

## 📌 Ringkasan

Sistem ini memungkinkan Anda untuk membuat dan mengelola **template folder** untuk setiap tahun arsip. Dengan template, Anda bisa:

✅ Membuat struktur folder standar yang bisa digunakan berulang kali  
✅ Menyesuaikan template sesuai kebutuhan organisasi Anda  
✅ Generate tahun baru dengan struktur yang konsisten  
✅ Set template default untuk digunakan otomatis

---

## 🎯 Fitur Utama

### 1. **Template Manager** (`template_manager.php`)

Halaman untuk mengelola semua template:

- ✏️ Membuat template baru
- 📝 Mengedit detail template (nama, deskripsi, set default)
- 🗂️ Menambah/menghapus folder di dalam template
- 🗑️ Menghapus template (kecuali template default)
- 📊 Melihat struktur folder dalam template secara visual (tree view)

### 2. **Generate from Template** (`generate.php`)

Proses generate tahun baru berdasarkan template:

- User memilih template yang ingin digunakan
- Sistem secara otomatis membuat struktur folder sesuai template
- Semua subfolder dan keterangan disalin secara otomatis

### 3. **Backward Compatibility**

Jika tidak ada action `generate`, sistem tetap bisa:

- Copy struktur dari tahun sebelumnya
- Gunakan sebagai fallback jika user tidak memilih template

---

## 📁 Database Schema

### Tabel: `templates`

```sql
CREATE TABLE `templates` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL UNIQUE,
  `description` text,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel: `template_folders`

```sql
CREATE TABLE `template_folders` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `template_id` int NOT NULL FOREIGN KEY REFERENCES templates(id) ON DELETE CASCADE,
  `parent_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `order_index` int DEFAULT 0
);
```

---

## 🚀 Cara Menggunakan

### Step 1: Akses Template Manager

Dari halaman utama, klik tombol **"Template"** di header atas atau di halaman generate.

```
http://localhost:8090/template_manager.php
```

### Step 2: Buat Template Baru

1. Klik tombol **"Template Baru"**
2. Isi nama template (misal: "Template 2026")
3. Isi deskripsi (opsional)
4. Centang **"Set sebagai default"** jika ingin jadikan default
5. Klik **"Buat"**

### Step 3: Tambah Folder ke Template

1. Pilih template dari daftar di sebelah kiri
2. Di section **"Struktur Folder"**, scroll ke bawah
3. Isi form **"Tambah Folder"**:
   - **Nama Folder**: Misal "PERDATA", "PIDANA", dsb
   - **Parent Folder**: Kosongkan untuk root, atau pilih folder parent
   - **Urutan**: Nomor urutan (untuk sorting)
   - **Deskripsi**: Keterangan folder (opsional)
4. Klik **"Tambah"**

### Step 4: Buat Tahun Baru dari Template

1. Dari halaman utama, klik **"Template / Generate"**
2. Sistem akan tampilkan form **"Pilih Template"**
3. Pilih template yang ingin digunakan
4. Klik **"Buat"**
5. Folder struktur akan dibuat otomatis sesuai template

---

## 📊 Contoh Template Default

Template yang sudah include:

```
Template Standar Pengadilan
├── PERDATA
│   ├── Gugatan
│   ├── Keberatan
│   └── Kuasa Hukum
├── PIDANA
│   ├── Penuntutan
│   ├── Keputusan
│   └── Berkala
├── TATA USAHA NEGARA
├── KOREKSI
└── ADMINISTRASI UMUM
```

Anda bisa memodifikasi atau buat template baru sesuai kebutuhan.

---

## 🔗 File-File yang Terlibat

| File                   | Fungsi                                         |
| ---------------------- | ---------------------------------------------- |
| `template_manager.php` | Halaman untuk manage template                  |
| `generate.php`         | Proses generate tahun dari template            |
| `index.php`            | Header ditambahkan tombol Template             |
| `app.js`               | Updated untuk redirect ke generate.php         |
| `db_arsip.sql`         | Ditambahkan tabel templates & template_folders |

---

## 🎨 UI & UX

### Template Manager Page

- **Left Sidebar**: Daftar semua template (dengan highlight active)
- **Main Content**: Detail template + form untuk manage
- **Form Input**: Dedicated area untuk add folder dengan parent selection dropdown

### Generate Page

- **Template Selection Modal**: Radio button list semua template
- **Visual Indication**: Badge "DEFAULT" untuk template default
- **Link ke Manager**: Quick access ke Template Manager dari halaman generate

---

## 💡 Tips & Best Practices

### ✅ DO:

- Gunakan naming convention yang jelas untuk folder (UPPERCASE)
- Buat deskripsi singkat tapi informatif
- Set satu template sebagai default
- Buat backup template sebelum mengubah
- Organizer folder secara hierarki yang logis

### ❌ DON'T:

- Jangan hapus template default (akan error)
- Jangan buat folder dengan nama yang sama di level yang sama
- Jangan mengubah struktur tahun yang sudah banyak data (risky)
- Jangan create folder terlalu dalam (> 3 level) - bisa membingungkan

---

## 🔧 Setup untuk Server CentOS

### 1. Import Database

```bash
mysql -u arsip_user -p arsip_db < db_arsip.sql
```

### 2. Set File Permissions

```bash
sudo chown -R apache:apache /var/www/html/arsip/
sudo chmod -R 755 /var/www/html/arsip/
sudo chmod -R 777 /var/www/html/arsip/uploads/
```

### 3. Verify Database Tables

```bash
mysql -u arsip_user -p arsip_db
SHOW TABLES;  # Harus ada: files, folders, templates, template_folders
```

---

## 🆘 Troubleshooting

### Error: "Nama template sudah ada"

**Solusi**: Gunakan nama unik untuk setiap template

### Error: "Tidak bisa mengubah template default"

**Solusi**: Set template lain sebagai default dulu, baru hapus template yang ingin dihapus

### Folder tidak muncul di hierarki

**Solusi**: Check parent_id di database, pastikan parent folder ada

### Generate tidak membuat folder

**Solusi**:

- Cek apakah template_id valid
- Cek database connection
- Check browser console untuk error message

---

## 📈 Future Enhancements

Ide untuk pengembangan lebih lanjut:

- [ ] Drag & drop untuk reorder folder dalam template
- [ ] Import/export template (JSON format)
- [ ] Template dari file/tahun sebelumnya
- [ ] Copy template ke template baru
- [ ] Folder template versioning
- [ ] Bulk add folder dari CSV

---

## 📞 Support

Untuk pertanyaan atau masalah, silakan:

1. Check file ini terlebih dahulu
2. Lihat database structure di `db_arsip.sql`
3. Debug di browser console (F12)
4. Check server logs: `/var/log/httpd/error_log` (CentOS)

Selamat menggunakan! 🎉
