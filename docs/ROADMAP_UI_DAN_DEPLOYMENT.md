# Roadmap Redesign UI & Spesifikasi Deployment

Berdasarkan *Software Requirements Specification (SRS)* dan integrasi nyata, Sprint 5 (Kontak, Pencarian, Peta) telah dinyatakan selesai. Sisa masa KKN difokuskan pada penyelarasan desain dengan *Figma Make* (Sprint 6) yang dibagi menjadi batch-batch UI yang ketat sebelum *handover* ke pihak desa.

## A. Roadmap Redesign UI (Admin & Guest)
- **Batch 1 (Selesai):** Admin Shell & Dashboard (Topbar, Sidebar *collapsed*, Dark mode).
- **Batch 2 (Selesai):** Admin Konten Utama (Berita, Halaman, kategori kontekstual, *footer form sticky*).
- **Batch 3 (Selesai):** Admin Media & Dokumen (Galeri, Media Library, alur *upload*, Ekspor Tabel CSV/PDF).
- **Batch 4 (Selesai):** Admin Pengelolaan Website (Navigasi/Menu Builder, Tampilan & Identitas Situs).
- **Batch 5 (Selesai):** Modul Lain & Polishing Admin (Peta & Lokasi tanpa mengubah *logic* Leaflet, Pesan Masuk, Log Aktivitas).
- **Batch 6 (Selesai):** Guest Shell & Layout Global (Header, Footer, tipografi, warna *frontend*, pencarian publik).
- **Batch 7 (Selesai):** Guest Beranda & Profil Desa (Statistik, potensi, berita terbaru, topologi desa).
- **Batch 8 (Selesai):** Guest Konten Publik (Daftar/detail Berita, Galeri, Dokumen, Peta, form kontak antispam).
- **Batch 9 (Selesai):** Integrasi & True Frontend Preview (Menyatukan setelan Admin ke Guest secara dinamis).
- **Batch 10 (Target Berikutnya):** Final Full-System Audit (Uji responsivitas, mode gelap/terang, *accessibility*, *regression test* total).

## B. Spesifikasi Deployment & Handover Desa
Sesuai filosofi infrastruktur *"Start Small, Scale Later"*:

### 1. Tahap KKN (Saat Ini)
- **Infrastruktur:** Shared Hosting (cPanel/VPS kecil) yang mendukung PHP 8.3+ dan PostgreSQL.
- **Domain:** Domain pengembangan ekonomis (misal: `desakertajaya.my.id`).
- **Jaringan & Keamanan:** Wajib dirutekan melalui **Cloudflare** dengan mode SSL `Full Strict`, WAF, dan pembatasan DDoS aktif.

### 2. Tahap Produksi & Handover (Masa Depan)
- **Domain:** Pengalihan ke domain pemerintahan (`desakertajaya.id` / `desa.id`).
- **Infrastruktur:** Migrasi ke VPS resmi desa. Penyimpanan media dapat diskalakan dari *local storage* ke *Object Storage*.
- **Rotasi Keamanan Terakhir:** Pembuatan kredensial baru, perombakan 100% *password* admin, penghapusan *recovery codes* pengembangan, pendaftaran rahasia TOTP baru ke perangkat desa, dan penggantian *keys* Cloudflare Turnstile menuju *production keys*.