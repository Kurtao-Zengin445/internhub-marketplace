# InternHub

InternHub adalah platform marketplace magang berbasis Laravel, MySQL, dan Bootstrap. Platform ini menghubungkan intern dengan perusahaan, mendukung lamaran magang, verifikasi perusahaan, subscription premium, presensi GPS/kamera, laporan harian, dokumen, dan evaluasi.

## Role

- `admin`
- `intern`
- `company`
- `supervisor`

## Setup Singkat

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Database default yang dipakai pada pengembangan lokal: `internhub_db`.

## Akun Seeder

Admin:

```text
admin@gmail.com
Password1
```

## Validasi

```bash
php artisan test
php artisan view:cache
```
