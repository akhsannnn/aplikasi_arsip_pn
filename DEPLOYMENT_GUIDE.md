# 📋 PANDUAN DEPLOYMENT KE SERVER CENTOS

## 🔍 Status Saat Ini (Windows + Docker)

```
Lokasi Source Code:  d:\Arya Files\code\aplikasi_arsip_pn\
Disimpan di:         Windows Host Machine
Runtime:             Docker Container (arsip_web)
Database:            MySQL dalam Docker Container (arsip_db)
```

---

## 🚀 OPSI DEPLOYMENT

### **OPSI A: Docker di Server CentOS (REKOMENDASI)**

**Keuntungan:**

- Environment sama dengan development
- Setup lebih cepat
- Mudah di-maintain

**Langkah:**

1. **Install Docker & Docker Compose di CentOS:**

```bash
sudo yum install -y docker docker-compose
sudo systemctl start docker
sudo systemctl enable docker
```

2. **Upload ke Server CentOS:**

```bash
# Dari Windows PowerShell (menggunakan SCP atau Git):
scp -r "d:\Arya Files\code\aplikasi_arsip_pn" user@centos-server:/home/user/
```

3. **Deploy dengan Docker Compose:**

```bash
cd /home/user/aplikasi_arsip_pn
docker compose up -d
```

4. **Akses aplikasi:**

```
http://centos-server-ip:8090
```

---

### **OPSI B: Install Manual di CentOS (Tanpa Docker)**

**Langkah:**

1. **Install Dependencies:**

```bash
sudo yum install -y php php-mysql apache2 mysql-server git
sudo systemctl start httpd
sudo systemctl start mysqld
```

2. **Setup MySQL Database:**

```bash
# Login MySQL
mysql -u root -p

# Create database & user
CREATE DATABASE arsip_db;
CREATE USER 'arsip_user'@'localhost' IDENTIFIED BY 'arsip_pass';
GRANT ALL PRIVILEGES ON arsip_db.* TO 'arsip_user'@'localhost';
FLUSH PRIVILEGES;

# Import database
mysql -u arsip_user -p arsip_db < /path/to/db/db_arsip.sql
```

3. **Copy Source Code:**

```bash
# Copy ke web root Apache
sudo cp -r aplikasi_arsip_pn/app/* /var/www/html/arsip/

# Set permissions
sudo chown -R apache:apache /var/www/html/arsip
sudo chmod -R 755 /var/www/html/arsip
sudo chmod -R 777 /var/www/html/arsip/uploads
```

4. **Update `db.php` untuk CentOS (jika berbeda):**

```php
<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'arsip_user';
$pass = getenv('DB_PASS') ?: 'arsip_pass';
$db   = getenv('DB_NAME') ?: 'arsip_db';

// atau hardcode untuk production:
// $host = 'localhost';
// $user = 'arsip_user';
// $pass = 'arsip_pass';
// $db = 'arsip_db';
?>
```

5. **Setup Apache Virtual Host:**

```bash
# File: /etc/httpd/conf.d/arsip.conf
<VirtualHost *:80>
    ServerName arsip.example.com
    DocumentRoot /var/www/html/arsip

    <Directory /var/www/html/arsip>
        AllowOverride All
        Require all granted
    </Directory>

    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </IfModule>
</VirtualHost>
```

6. **Restart Apache:**

```bash
sudo systemctl restart httpd
```

---

## 📁 Struktur File untuk Production

```
/var/www/html/arsip/                    (OPSI B) atau /home/user/aplikasi_arsip_pn (OPSI A)
├── app/
│   ├── api.php
│   ├── app.js
│   ├── db.php                ← Pastikan DB config benar!
│   ├── index.php
│   ├── uploads/              ← Harus writable (chmod 777)
│   │   ├── 2025/
│   │   └── 2026/
│   └── style.css
├── db/
│   └── db_arsip.sql          ← SQL dump (sudah imported)
├── docker-compose.yaml       (OPSI A only)
└── Dockerfile                (OPSI A only)
```

---

## 🔐 Keamanan Production

**PENTING - Jangan gunakan di production:**

- Credentials hardcoding di db.php
- Display errors di PHP

**Gunakan:**

- Environment variables (`.env` file)
- Secrets management
- HTTPS/SSL certificate
- Firewall rules

---

## 📦 Paket untuk di-Upload ke Server

Siapkan zip dengan struktur:

```
aplikasi_arsip_pn.zip
├── app/                   (semua file PHP, JS, CSS)
├── db/                    (db_arsip.sql)
├── docker-compose.yaml    (untuk OPSI A)
├── Dockerfile             (untuk OPSI A)
└── README.md
```

---

## ✅ Checklist Deployment

- [ ] Database credentials sudah di-update sesuai CentOS
- [ ] Folder `uploads/` writable (chmod 777)
- [ ] PHP extensions: mysqli, pdo_mysql installed
- [ ] Apache/Nginx sudah running
- [ ] MySQL sudah running
- [ ] Firewall allow port 80 (atau 8090 jika Docker)
- [ ] Test akses dari browser
- [ ] Backup database before going live

---

## 🆘 Troubleshooting

**Error: "Koneksi Database Gagal"**

- Cek MySQL running: `sudo systemctl status mysqld`
- Cek credentials di db.php
- Cek MySQL user permissions

**Error: "Tidak bisa upload file"**

- Set permissions folder uploads: `sudo chmod 777 /var/www/html/arsip/uploads`
- Cek PHP post_max_size & upload_max_filesize

**Error: "404 Not Found"**

- Cek Apache DocumentRoot pointing ke folder yang benar
- Enable mod_rewrite: `sudo a2enmod rewrite`

---

## 📞 Catatan

Repo ini sudah siap deploy ke CentOS dengan kedua opsi di atas.
Pilih yang sesuai kebutuhan infrastructure Anda.
