# Deploy InternHub ke Oracle Cloud Always Free

Panduan ini ditujukan untuk Oracle Cloud Always Free memakai Ubuntu, Nginx, MySQL, PHP-FPM, dan Supervisor queue worker.

## 1. Buat VM Always Free

Di Oracle Cloud Console:

1. Buka `Compute > Instances > Create instance`.
2. Pilih image `Ubuntu 22.04` atau `Ubuntu 24.04`.
3. Shape yang disarankan:
   - `VM.Standard.A1.Flex` jika tersedia, misalnya 1 OCPU dan 6 GB RAM.
   - Alternatif: `VM.Standard.E2.1.Micro`, tapi RAM kecil sehingga build asset lebih nyaman dilakukan lokal.
4. Tambahkan SSH public key dari laptop kamu.
5. Simpan public IP instance.

Di `Virtual Cloud Network > Security Lists` atau `Network Security Groups`, buka inbound:

- TCP `22` untuk SSH
- TCP `80` untuk HTTP
- TCP `443` untuk HTTPS

## 2. Login ke Server

```bash
ssh ubuntu@SERVER_PUBLIC_IP
```

Update server:

```bash
sudo apt update
sudo apt upgrade -y
```

## 3. Install Package

```bash
sudo apt install -y nginx mysql-server git unzip curl supervisor software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
```

Install Composer:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

Jika ingin build asset di server:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 4. Siapkan Database

```bash
sudo mysql
```

Di prompt MySQL:

```sql
CREATE DATABASE internhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'internhub_user'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON internhub_db.* TO 'internhub_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Clone Project

```bash
sudo mkdir -p /var/www
sudo chown -R ubuntu:www-data /var/www
cd /var/www
git clone https://github.com/Kurtao-Zengin445/internhub-marketplace.git internhub
cd internhub
```

## 6. Install Dependency Project

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Jika RAM server terlalu kecil dan `npm run build` gagal, jalankan `npm run build` di laptop, lalu upload folder `public/build` ke server.

## 7. Konfigurasi `.env`

```bash
cp .env.production.example .env
php artisan key:generate
nano .env
```

Minimal ganti:

```env
APP_URL=http://SERVER_PUBLIC_IP
DB_DATABASE=internhub_db
DB_USERNAME=internhub_user
DB_PASSWORD=GANTI_PASSWORD_KUAT
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
```

Jika sudah punya domain, gunakan:

```env
APP_URL=https://domain-kamu.com
```

## 8. Permission dan Storage

```bash
sudo chown -R ubuntu:www-data /var/www/internhub
sudo chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

## 9. Migrasi Database

Production tidak boleh memakai `migrate:fresh`.

```bash
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
```

Seeder admin default:

```text
admin@gmail.com
Password1
```

## 10. Nginx

Salin template:

```bash
sudo cp deploy/nginx/internhub.conf.example /etc/nginx/sites-available/internhub
sudo nano /etc/nginx/sites-available/internhub
```

Ganti:

- `your-domain.com` menjadi domain atau IP server.
- `fastcgi_pass` jika versi PHP-FPM berbeda.

Aktifkan:

```bash
sudo ln -s /etc/nginx/sites-available/internhub /etc/nginx/sites-enabled/internhub
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 11. Queue Worker

Salin template Supervisor:

```bash
sudo cp deploy/supervisor/internhub-worker.conf.example /etc/supervisor/conf.d/internhub-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start internhub-worker:*
sudo supervisorctl status
```

Jika belum siap menjalankan worker, ubah sementara `.env`:

```env
QUEUE_CONNECTION=sync
```

Lalu:

```bash
php artisan optimize:clear
```

## 12. Cache Production

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 13. Firewall Server

Ubuntu biasanya memakai `ufw`. Buka HTTP/HTTPS:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
sudo ufw status
```

Oracle juga punya firewall network di VCN. Pastikan port 80/443 dibuka di console Oracle, bukan hanya di Ubuntu.

## 14. SSL Gratis

Jika sudah punya domain yang mengarah ke public IP server:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d domain-kamu.com
```

Setelah SSL aktif, ubah `.env`:

```env
APP_URL=https://domain-kamu.com
```

Lalu:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 15. Midtrans Callback

Di dashboard Midtrans, set callback URL:

```text
https://domain-kamu.com/payments/midtrans/callback
```

Untuk awal demo, sandbox cukup:

```env
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com/snap/v1
```

## 16. Checklist Smoke Test

- Buka homepage.
- Login admin.
- Buat/verifikasi company.
- Login company dan buka lowongan.
- Login intern dan kirim lamaran.
- Company accept/reject lamaran.
- Upload file bisa terbuka melalui `/storage/...`.
- Queue worker tidak error.
- Subscription Midtrans sandbox mengaktifkan premium 30 hari.

## 17. Update Project Setelah Ada Perubahan

```bash
cd /var/www/internhub
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo systemctl reload nginx
```
