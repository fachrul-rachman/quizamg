# Panduan Setup Production (Debian 13 + Nginx + PHP 8.4 + Postgres)

Panduan ini dibuat untuk project Laravel (Livewire) yang sudah ada di server di:

- Lokasi project: `/var/www/quiz`
- Web server: Nginx
- PHP: 8.4.16 (PHP-FPM)
- Database: PostgreSQL

Tujuan panduan ini: dari “project sudah ada di server” → jadi web bisa diakses dan dipakai dengan aman.

> Catatan penting: di project ini ada fitur yang **butuh cron jalan tiap menit** untuk otomatisasi (expired link + kirim ringkasan ke Discord). Jadi bagian “Cron” jangan dilewati.

---

## 0) Sebelum mulai (cek cepat)

1. Pastikan domain/subdomain kamu sudah mengarah ke IP server (kalau pakai domain).
2. Pastikan folder project ada:
   - `/var/www/quiz/artisan`
   - `/var/www/quiz/public/index.php`
3. Pastikan kamu bisa akses server via SSH sebagai user yang boleh `sudo`.

---

## 1) Install kebutuhan dasar (sekali saja)

Jalankan ini di server:

```bash
sudo apt update
sudo apt install -y nginx postgresql postgresql-contrib \
  unzip git curl ca-certificates
```

Lalu install PHP 8.4 + ekstensi yang dibutuhkan aplikasi (biar fitur upload Excel, gambar, PDF, dan HTTP request jalan):

```bash
sudo apt install -y php8.4-fpm php8.4-cli php8.4-common \
  php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath \
  php8.4-opcache
```

Kalau `composer` belum ada, install Composer:

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

---

## 2) Buat database PostgreSQL untuk aplikasi

Tujuan langkah ini: bikin “tempat data” untuk aplikasi.

1) Masuk ke PostgreSQL:

```bash
sudo -u postgres psql
```

2) Di dalam prompt `psql`, jalankan (ganti password-nya):

```sql
CREATE USER quiz_user WITH PASSWORD 'GANTI_PASSWORD_YANG_KUAT';
CREATE DATABASE quiz_db OWNER quiz_user;
GRANT ALL PRIVILEGES ON DATABASE quiz_db TO quiz_user;
```

3) Keluar:

```sql
\q
```

---

## 3) Siapkan file konfigurasi aplikasi (`.env`)

Tujuan langkah ini: kasih tahu aplikasi “alamat web” dan “database mana yang dipakai”.

1) Masuk folder project:

```bash
cd /var/www/quiz
```

2) Kalau belum ada `.env`, copy dari contoh:

```bash
cp -n .env.example .env
```

3) Edit `.env`:

```bash
nano .env
```

Yang wajib kamu set (contoh):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=quiz_db
DB_USERNAME=quiz_user
DB_PASSWORD=GANTI_PASSWORD_YANG_KUAT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Catatan:
- `APP_URL` harus sesuai domain asli (penting untuk link dan file gambar).
- Fitur Google Drive dan Discord **boleh tetap dimatikan** kalau belum dipakai. Nanti bisa diaktifkan dengan isi variabel `GOOGLE_DRIVE_*` / `DISCORD_WEBHOOK_*`.

---

## 4) Install library PHP (Composer) dan rapihin folder izin akses

Tujuan langkah ini: aplikasi punya “komponen” yang dibutuhkan dan foldernya bisa ditulis.

> Tips: usahakan jangan jalankan `composer` dan `php artisan` sebagai `root` kalau tidak perlu. Kalau terlanjur, tidak masalah—nanti kita rapihin izin foldernya lagi.

1) Install dependency PHP (untuk production):

```bash
cd /var/www/quiz
composer install --no-dev --optimize-autoloader
```

2) Pastikan folder yang perlu ditulis bisa ditulis oleh server (Nginx/PHP biasanya pakai user `www-data`):

```bash
sudo chown -R www-data:www-data /var/www/quiz/storage /var/www/quiz/bootstrap/cache
sudo chmod -R ug+rwx /var/www/quiz/storage /var/www/quiz/bootstrap/cache
```

---

## 5) Jalankan perintah setup Laravel (wajib)

Tujuan langkah ini: bikin kunci keamanan, bikin tabel database, dan nyiapin link untuk gambar upload.

1) Generate APP_KEY (kalau `.env` baru / `APP_KEY` kosong):

```bash
cd /var/www/quiz
php artisan key:generate --force
```

2) Jalankan migrasi database (membuat tabel-tabel):

```bash
php artisan migrate --force
```

3) Buat user admin awal (ini akan buat 2 akun default):

```bash
php artisan db:seed --force
```

Akun default yang dibuat:
- `superadmin@amg.com` / password: `password`
- `admin@amg.com` / password: `password`

Setelah aplikasi hidup, **langsung ganti password** lewat menu Admin Users (harus login sebagai Super Admin).

4) Buat “jembatan” agar file gambar upload bisa diakses dari web:

```bash
php artisan storage:link
```

5) Rapihin performa (biar lebih cepat di production):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Kalau setelah menjalankan perintah-perintah di atas ada masalah permission (misalnya gambar gagal tersimpan), jalankan lagi:

```bash
sudo chown -R www-data:www-data /var/www/quiz/storage /var/www/quiz/bootstrap/cache
sudo chmod -R ug+rwx /var/www/quiz/storage /var/www/quiz/bootstrap/cache
```

---

## 6) Atur batas upload file (biar import Excel & upload gambar tidak gagal)

Project ini punya:
- Import Excel `.xlsx`
- Upload gambar soal dan opsi (maks 2MB per gambar di aplikasi)

Tapi server juga harus mengizinkan upload.

1) Atur Nginx (contoh kita set 20MB):
   - Nanti di konfigurasi Nginx, tambahkan: `client_max_body_size 20m;`

2) Atur PHP (contoh 20MB):

Cari file ini (tergantung server, salah satu biasanya ada):
- `/etc/php/8.4/fpm/php.ini`

Edit:

```ini
upload_max_filesize = 20M
post_max_size = 20M
```

Lalu restart PHP-FPM:

```bash
sudo systemctl restart php8.4-fpm
```

---

## 7) Konfigurasi Nginx untuk Laravel (wajib)

Tujuan langkah ini: Nginx mengarah ke folder `public/` Laravel.

1) Buat file config Nginx:

```bash
sudo nano /etc/nginx/sites-available/quiz
```

2) Isi contoh ini (ganti `domain-kamu.com`):

```nginx
server {
    listen 80;
    server_name domain-kamu.com;

    root /var/www/quiz/public;
    index index.php;

    # Biar upload tidak gagal
    client_max_body_size 20m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    # Lindungi file tersembunyi (contoh: .env)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache untuk file statis (opsional, tapi membantu)
    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|ico|woff|woff2|ttf)$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800";
        try_files $uri =404;
    }
}
```

3) Aktifkan site dan test config:

```bash
sudo ln -sf /etc/nginx/sites-available/quiz /etc/nginx/sites-enabled/quiz
sudo nginx -t
sudo systemctl reload nginx
```

Kalau Nginx komplain “duplicate default server” atau domain tidak kebaca, coba nonaktifkan config default bawaan:

```bash
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

Sekarang coba buka:
- `http://domain-kamu.com`
- `http://domain-kamu.com/admin/login`

---

## 8) Aktifkan Cron Laravel (wajib, karena ada job tiap menit)

Project ini punya jadwal otomatis tiap menit untuk:
- menandai link quiz yang sudah expired
- kirim ringkasan ke Discord (kalau Discord webhook diaktifkan)

Cara paling simpel: pasang cron yang menjalankan `schedule:run` tiap menit.

1) Buat file cron:

```bash
sudo nano /etc/cron.d/quiz
```

2) Isi:

```cron
* * * * * www-data cd /var/www/quiz && php artisan schedule:run >> /dev/null 2>&1
```

3) Pastikan cron jalan:

```bash
sudo systemctl enable --now cron
```

---

## 9) (Opsional tapi disarankan) Nyalakan Queue Worker

Aplikasi ini diset pakai queue database (`QUEUE_CONNECTION=database`). Kalau suatu saat ada proses “jalan di belakang layar”, queue worker bikin prosesnya tidak nyangkut.

1) Buat service systemd:

```bash
sudo nano /etc/systemd/system/quiz-queue.service
```

2) Isi:

```ini
[Unit]
Description=Quiz Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/quiz
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=1 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

3) Aktifkan:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now quiz-queue
sudo systemctl status quiz-queue --no-pager
```

---

## 10) (Opsional) Pasang HTTPS (biar aman)

Kalau kamu pakai domain publik, HTTPS itu wajib untuk keamanan.

Paling umum: pakai Let’s Encrypt (Certbot). Kalau kamu mau, bilang aja — aku bisa buatin langkah yang sesuai domain kamu.

---

## 11) Checklist setelah live (biar yakin aman)

1) Login ke Admin:
   - Buka `/admin/login`
   - Login pakai `superadmin@amg.com` / `password`
2) Segera ganti password akun default.
3) Coba bikin quiz dan upload gambar (pastikan gambar muncul).
4) Coba import dari file `Template Quiz.xlsx` (menu template ada di admin).
5) Coba kerjakan quiz sebagai peserta, pastikan hasil keluar.
6) Cek log kalau ada error:
   - Laravel: `/var/www/quiz/storage/logs/laravel.log`
   - Nginx: biasanya `/var/log/nginx/error.log`

---

## 12) Kalau ada error yang paling sering

- Halaman putih / error 500:
  - Cek `/var/www/quiz/storage/logs/laravel.log`
- Database error:
  - Pastikan `.env` sudah `DB_CONNECTION=pgsql` dan user/db benar
  - Pastikan sudah `php artisan migrate --force`
- Upload gagal:
  - Pastikan `client_max_body_size` di Nginx cukup
  - Pastikan `upload_max_filesize` dan `post_max_size` di PHP cukup
- Gambar tidak muncul:
  - Pastikan sudah `php artisan storage:link`
