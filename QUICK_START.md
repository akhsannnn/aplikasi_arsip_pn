# 🎯 QUICK START - TEMPLATE SYSTEM

## ✅ Yang Baru Saja Disetup

Anda sekarang memiliki **Template Management System** yang lengkap untuk mengelola struktur folder setiap tahun.

### File Baru / Updated:

- ✅ `app/template_manager.php` - Admin panel untuk kelola template
- ✅ `app/generate.php` - Updated untuk support template selection
- ✅ `db/db_arsip.sql` - Updated dengan tabel templates & template_folders
- ✅ `TEMPLATE_GUIDE.md` - Dokumentasi lengkap fitur template

---

## 🚀 Langkah Pertama

### 1. Buka Aplikasi

```
http://localhost:8090
```

### 2. Akses Template Manager

Klik tombol **"Template"** di header (tombol baru di sebelah kiri "Template / Generate")

### 3. Lihat Template Default

Sudah ada 1 template default: **"Template Standar Pengadilan"** dengan struktur:

```
PERDATA
PIDANA
TATA USAHA NEGARA
KOREKSI
ADMINISTRASI UMUM
```

### 4. Tambah/Edit Template

Dari Template Manager, Anda bisa:

- Membuat template baru
- Menambah/menghapus folder dalam template
- Set sebagai default
- Edit nama & deskripsi

### 5. Generate Tahun Baru

Dari halaman utama, klik **"Template / Generate"** lalu:

- Pilih template yang ingin digunakan
- Sistem akan create struktur folder otomatis
- Selesai!

---

## 📊 Cara Kerja

### Sebelumnya (Manual Copy):

```
Generate Tahun Baru → Copy dari Tahun Lalu
```

### Sekarang (Template-Based):

```
Kelola Template → Generate dari Template
                      ↓
              Konsisten & Fleksibel
```

---

## 🎨 UI Changes

### Header Baru (index.php):

- Tombol **"Template"** (gear icon) - Buka Template Manager
- Tombol **"Template / Generate"** (copy icon) - Generate tahun baru

### New Page: template_manager.php

- Left sidebar: Daftar template
- Main area: Edit template + manage folder structure
- Form: Add new folder dengan parent selection

### Updated: generate.php

- Tampilan pilih template (modal)
- Radio button untuk pilih template
- Badge "DEFAULT" untuk template default

---

## 🔄 Database Schema

Tabel baru yang sudah ditambahkan:

```sql
templates
├── id (PK)
├── name (unique)
├── description
├── is_default
├── created_at
└── updated_at

template_folders
├── id (PK)
├── template_id (FK → templates)
├── parent_id (self-referencing)
├── name
├── description
└── order_index
```

---

## 💻 Struktur Folder

Setelah setup, struktur folder Anda:

```
aplikasi_arsip_pn/
├── app/
│   ├── index.php          (updated)
│   ├── app.js             (updated)
│   ├── generate.php       (updated - besar!)
│   ├── template_manager.php (NEW!)
│   ├── api.php
│   ├── db.php
│   └── uploads/
├── db/
│   ├── db_arsip.sql       (updated)
│   ├── add_templates_table.sql
│   └── insert_default_folders.sql
├── TEMPLATE_GUIDE.md      (NEW!)
├── DEPLOYMENT_GUIDE.md
└── [lainnya...]
```

---

## 🧪 Testing Checklist

- [ ] Akses `http://localhost:8090` - OK?
- [ ] Klik tombol "Template" di header - Buka template_manager.php?
- [ ] Lihat 1 template default di list - Ada?
- [ ] Klik "Template / Generate" - Show template selection?
- [ ] Pilih template → Klik "Buat" - Create folder struktur?
- [ ] Cek folder yang dibuat - Structure sesuai template?

---

## 🔌 API Endpoints (if needed)

Dalam `api.php` sudah ada action:

- `action=get_content` - Get folder & file list
- `action=generate_custom` - Generate dari template (POST)
- `action=delete_year` - Delete year
- `action=delete_folder` - Delete folder
- `action=delete_file` - Delete file
- dst...

---

## 📝 Next Steps

1. **Customize Template**

   - Buka Template Manager
   - Edit nama/deskripsi
   - Tambah folder sesuai kebutuhan Anda
   - Set sebagai default

2. **Buat Template Tambahan**

   - Contoh: "Template Pengadilan Tipikor", "Template Pajak", dll
   - Setiap template bisa punya struktur berbeda

3. **Generate Tahun Baru**

   - User baru ke 2027? Gunakan "Template / Generate"
   - Pilih template → Selesai!

4. **Deploy ke CentOS**
   - Semua file sudah siap
   - Lihat `DEPLOYMENT_GUIDE.md`
   - Database akan otomatis create tabel saat import

---

## 🆘 Quick Troubleshooting

| Issue                             | Solusi                                                                  |
| --------------------------------- | ----------------------------------------------------------------------- |
| Template Manager tidak muncul     | Cek docker running, hard refresh (Ctrl+F5)                              |
| Folder tidak muncul saat generate | Cek database: `SELECT * FROM template_folders;`                         |
| Template tidak bisa dihapus       | Template default tidak bisa dihapus - set template lain sebagai default |
| Generate error                    | Cek console (F12), database connection                                  |

---

## 📚 Dokumentasi

Baca lengkap di:

- **`TEMPLATE_GUIDE.md`** - Dokumentasi lengkap fitur template
- **`DEPLOYMENT_GUIDE.md`** - Setup di server CentOS

---

## 🎉 Selesai!

Sistem template sudah siap digunakan. Nikmati kemudahan mengelola struktur folder untuk setiap tahun!

Pertanyaan? Check dokumentasi atau inspect database langsung di phpMyAdmin:

```
http://localhost:8091
User: arsip_user
Pass: arsip_pass
```
