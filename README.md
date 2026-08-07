# Sistem Informasi & Website Desa Kertajaya (CMS)

Selamat datang di repositori resmi **Website & Content Management System (CMS) Desa Kertajaya**. 
Sistem ini dibangun secara khusus (Custom-Built) menggunakan teknologi web modern untuk membantu pemerintah Desa Kertajaya mengelola informasi, potensi desa, dan pelayanan masyarakat secara digital dengan aman dan responsif.

---

## 🌟 Fitur Unggulan

- **Panel Admin Berbasis Filament:** Antarmuka pengelola konten yang elegan, responsif, dan mudah digunakan (User Friendly).
- **Keamanan Lapis Baja (Single Session):** Akses admin dikunci ketat; hanya satu admin atau perangkat yang dapat aktif dalam satu waktu untuk mencegah pembajakan sesi.
- **Sistem Perpustakaan Media (Media Library):** Mengelola foto dan dokumen terpusat, lengkap dengan fitur pemberian tanda air (Watermarking) otomatis dan anti-maling.
- **Dinamis & Terintegrasi:** Berita desa, galeri kegiatan, struktur organisasi, hingga manajemen kontak keluhan warga terhubung dalam satu portal utama.
- **Arsitektur Berkinerja Tinggi:** Dioptimasi (Hyper-Optimized) agar web memuat dalam waktu seketika menggunakan mekanisme *Caching* tingkat lanjut pada *Views*, *Routes*, dan *Config*.

## 🛠 Teknologi yang Digunakan

*   **Framework Inti:** Laravel (PHP)
*   **Admin Panel:** Filament
*   **Basis Data:** PostgreSQL
*   **Tampilan Depan (Frontend):** Blade Templates & Tailwind CSS
*   **Pemrosesan Sinkron:** Sistem *Queue* (Antrean) dirancang menggunakan mode `sync` untuk penanganan data secara seketika (*real-time*).

## 🚀 Panduan Instalasi (Untuk Pengembang/Developer)

Jika Anda ingin menjalankan sistem ini di komputer lokal, ikuti langkah berikut:

1. **Kloning Repositori:**
   ```bash
   git clone https://github.com/UsernameAnda/nama-repo-anda.git
   cd village-cms
   ```

2. **Instalasi Dependensi:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Sesuaikan pengaturan koneksi database (PostgreSQL) di file `.env`.*

4. **Migrasi Database & Penyemaian Data (Seeding):**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
   *Akses web publik di `http://127.0.0.1:8000` dan Dasbor Admin di `http://127.0.0.1:8000/desa-dashboard`.*

---

## 🔒 Catatan Keamanan Penting (Deployment)
Untuk peluncuran (*deployment*) ke VPS atau Server Produksi, pastikan:
1. `APP_ENV=production` dan `APP_DEBUG=false`.
2. Lakukan optimalisasi penuh: `php artisan optimize` dan `php artisan view:cache`.
3. Hak akses folder (*Permission*) untuk `storage/` dan `bootstrap/cache/` diatur dengan tepat agar log dan *cache* tidak *error*.

---
*Dibangun dan dikembangkan dengan bangga dalam rangka mewujudkan Desa Kertajaya yang Go-Digital.*
