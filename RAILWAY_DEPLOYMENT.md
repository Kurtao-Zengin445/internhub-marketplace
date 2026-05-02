# Deploy InternHub ke Railway

Panduan ini untuk deploy InternHub dari GitHub ke Railway.

## Catatan Gratis

Railway cocok untuk demo cepat karena setup-nya sederhana. Namun plan gratis/trial Railway tidak sama seperti VPS gratis permanen: cek pricing terbaru sebelum dipakai jangka panjang.

## 1. Siapkan Project Railway

1. Login ke Railway.
2. Pilih `New Project`.
3. Pilih `Deploy from GitHub repo`.
4. Pilih repo:

```text
Kurtao-Zengin445/internhub-marketplace
```

5. Tambahkan database MySQL dari Railway project canvas.

## 2. Variables

Di service Laravel, buka `Variables`, lalu copy isi `.env.railway`.

Wajib ganti:

```env
APP_KEY=base64:...
APP_URL=https://domain-railway-kamu.up.railway.app
MAIL_*
MIDTRANS_*
```

Generate `APP_KEY` di lokal:

```bash
php artisan key:generate --show
```

Railway MySQL biasanya menyediakan variable ini:

```env
MYSQLHOST
MYSQLPORT
MYSQLDATABASE
MYSQLUSER
MYSQLPASSWORD
```

Template `.env.railway` memakai reference:

```env
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}
```

## 3. Build dan Start

Project ini sudah memiliki `railway.json`.

Build command:

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

Pre-deploy command:

```bash
bash deploy/railway/migrate.sh
```

Start command:

```bash
bash deploy/railway/start.sh
```

Pre-deploy hanya menjalankan migration. Jangan pakai `migrate:fresh` di Railway karena akan menghapus data.

## 4. Seeder Admin

Setelah deploy pertama sukses, buka Railway Shell atau jalankan command manual:

```bash
php artisan db:seed --class=AdminSeeder --force
```

Akun admin default:

```text
admin@gmail.com
Password1
```

## 5. Queue Email

Untuk demo paling mudah:

```env
QUEUE_CONNECTION=sync
```

Kalau ingin queue worker yang benar:

1. Buat service baru dari repo yang sama.
2. Set start command service worker:

```bash
bash deploy/railway/worker.sh
```

3. Pastikan variables worker sama dengan service web.

## 6. Upload File dan Storage

InternHub memakai upload CV, dokumen, logo, foto, dan presensi. Pada Railway, filesystem app bisa tidak permanen untuk file upload.

Untuk demo singkat:

```env
FILESYSTEM_DISK=public
```

Untuk penggunaan lebih aman, gunakan salah satu:

- Railway Volume yang dipasang ke path storage.
- S3-compatible storage seperti Cloudflare R2, AWS S3, atau provider lain.

Jika memakai Railway Volume, pastikan path upload Laravel tetap tersedia setelah redeploy.

## 7. Midtrans

Untuk sandbox:

```env
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/v1
```

Set callback URL di dashboard Midtrans:

```text
https://domain-railway-kamu.up.railway.app/payments/midtrans/callback
```

## 8. Google Login

Jika memakai Google OAuth, set authorized redirect URI:

```text
https://domain-railway-kamu.up.railway.app/auth/google/callback
```

Lalu isi:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

## 9. Checklist Setelah Deploy

- Homepage terbuka.
- Login admin berhasil.
- Database migration berhasil.
- AdminSeeder sudah dijalankan.
- Company bisa login dan membuka lowongan.
- Intern bisa melamar lowongan.
- Company bisa accept/reject lamaran.
- Email terkirim atau queue sync berjalan.
- Midtrans sandbox bisa redirect dan callback.
- Upload file tetap bisa dibuka dari `/storage/...`.

## 10. Troubleshooting

Jika error `APP_KEY`:

```bash
php artisan key:generate --show
```

Copy hasilnya ke Railway variable `APP_KEY`.

Jika error database:

- Pastikan service MySQL sudah dibuat.
- Pastikan Laravel service dan MySQL berada dalam project yang sama.
- Pastikan `DB_*` memakai Railway reference variable.

Jika asset CSS/JS tidak muncul:

- Cek build log apakah `npm run build` sukses.
- Pastikan folder `public/build` terbentuk saat build.

Jika upload hilang setelah redeploy:

- Gunakan Railway Volume atau S3-compatible storage.
