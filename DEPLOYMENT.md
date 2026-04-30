# Deployment InternHub

Panduan ini dipakai saat InternHub akan dihosting sebagai aplikasi Laravel production.

## 1. Persiapan Repository

Pastikan perubahan sudah tersimpan di Git:

```bash
git status
git log --oneline -5
```

Jika belum punya remote GitHub:

```bash
git remote add origin https://github.com/username/internhub.git
git push -u origin main
```

## 2. Requirement Server

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm untuk build asset
- MySQL atau MariaDB
- Web server Nginx/Apache mengarah ke folder `public`
- Extension PHP umum Laravel: `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `curl`, `zip`

## 3. Environment Production

Salin template production:

```bash
cp .env.production.example .env
php artisan key:generate
```

Atur minimal:

- `APP_URL`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_*`
- `GOOGLE_*` jika login Google dipakai
- `MIDTRANS_*`

Gunakan:

```env
APP_ENV=production
APP_DEBUG=false
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
```

## 4. Install Dependency dan Build Asset

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Jika hosting tidak menyediakan Node.js, build asset di lokal lalu upload folder `public/build`.

## 5. Database

Jangan menjalankan `migrate:fresh` di production karena akan menghapus data.

```bash
php artisan migrate --force
```

Seeder hanya dijalankan jika memang ingin membuat data awal:

```bash
php artisan db:seed --class=AdminSeeder --force
```

## 6. Storage Upload

InternHub memakai upload CV, dokumen, foto, dan verifikasi perusahaan. Jalankan:

```bash
php artisan storage:link
```

Pastikan folder ini writable oleh web server:

```text
storage
bootstrap/cache
```

## 7. Cache Production

Setelah `.env` benar:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Jika mengubah `.env`, jalankan ulang:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 8. Queue Email

Email status lamaran dan laporan memakai queue. Jalankan worker:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Di VPS, pasang worker lewat Supervisor/systemd. Jika hosting tidak mendukung worker, gunakan sementara:

```env
QUEUE_CONNECTION=sync
```

## 9. Midtrans

Untuk sandbox:

```env
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/v1
```

Untuk production Midtrans, ubah key dan URL sesuai dashboard Midtrans.

Callback URL yang perlu didaftarkan:

```text
https://your-domain.com/payments/midtrans/callback
```

## 10. Checklist Setelah Deploy

- Halaman `/` terbuka
- Login admin berhasil
- Company bisa upload/ubah profil
- Admin bisa verifikasi company
- Intern bisa melamar lowongan
- Company bisa accept/reject lamaran
- Email terkirim
- Upload file bisa dibuka dari `/storage/...`
- Subscription Midtrans sandbox berhasil mengaktifkan premium 30 hari

## 11. Perintah Validasi

```bash
php artisan about
php artisan route:list --except-vendor
php artisan test
```
