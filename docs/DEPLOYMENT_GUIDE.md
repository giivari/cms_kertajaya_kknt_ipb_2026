# Panduan Deployment Village CMS (Tahap KKN)

Sistem secara arsitektur sudah **SIAP DEPLOYMENT** untuk versi MVP (Minimum Viable Product) tahap KKN. Semua fitur inti (Admin, Publik, Media, MFA) telah selesai.

Berikut adalah langkah-langkah *deployment* ke Shared Hosting (cPanel) atau VPS Anda:

## 1. Persiapan Build di Komputer Lokal (Windows)

Sebelum file diunggah ke server, kita harus mem-*build* aset frontend dan mengoptimalkan aplikasi.

Jalankan perintah ini di PowerShell lokal Anda:

```powershell
cd C:\Users\givar\KULIAH\WEB_KKN\village-cms
# 1. Pastikan semua modul terinstal rapi
composer install --optimize-autoloader --no-dev
npm install

# 2. Build aset frontend (Vite) untuk produksi
npm run build

# 3. Hapus cache lokal agar tidak terbawa
php artisan optimize:clear
```

## 2. Unggah File ke Server (Shared Hosting / VPS)

1. Kompres seluruh isi folder `village-cms` menjadi `village-cms.zip` (Kecuali folder `.git`, `node_modules`, dan `tests`).
2. Unggah `village-cms.zip` ke server Anda (misalnya diletakkan di luar `public_html` atau di `/var/www/village-cms`).
3. Ekstrak file tersebut.
4. (Khusus cPanel) Buat *symlink* atau arahkan *Document Root* domain `desakertajaya.site` ke folder `public` di dalam hasil ekstraksi `village-cms/public`.

## 3. Konfigurasi Environment di Server (`.env`)

Ubah file `.env` di server Anda dengan pengaturan *Production*:

```env
APP_NAME="CMS Desa Kertajaya"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://desakertajaya.site

# Sesuaikan dengan kredensial PostgreSQL di server
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_db_server
DB_USERNAME=user_db_server
DB_PASSWORD=password_db_server

# Jangan lupa isi Turnstile Key asli jika sudah punya, atau biarkan testing key sementara
TURNSTILE_SITE_KEY=1x00000000000000000000AA
TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
```

## 4. Eksekusi Perintah Akhir di Server (via SSH / Terminal cPanel)

Masuk ke terminal server Anda, arahkan ke direktori project, lalu jalankan:

```bash
# 1. Jalankan migrasi database (Tabel akan dibuat KOSONG BERSIH di server)
php artisan migrate --force

# 2. Buat akun Admin pertama Anda di server production
php artisan make:filament-user

# 3. Buat symlink untuk storage media
php artisan storage:link

# 4. Cache semua konfigurasi dan rute agar website super cepat
php artisan optimize
```

## 5. Pengaturan Cloudflare (Keamanan)

1. Tambahkan domain `desakertajaya.site` ke Cloudflare.
2. Di menu **SSL/TLS**, pilih mode **Full (Strict)**.
3. Di menu **Turnstile**, buat *Site Key* dan *Secret Key* baru untuk domain tersebut, lalu masukkan ke `.env` server dan jalankan ulang `php artisan config:cache`.
