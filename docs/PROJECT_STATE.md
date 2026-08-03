# Village Website CMS Desa Kertajaya

## 1. Identitas Proyek & Arsitektur
- **Workspace Aktif:** `C:\Users\givar\KULIAH\WEB_KKN\village-cms`
- **Branch Aktif:** `main`
- **HEAD:** (Latest Commit on Main)
- **Arsitektur Utama:** Single Village Installation, Single Admin Panel (tidak ada multi-admin).
- **Teknologi:** Laravel 12, Filament 4, PostgreSQL, Tailwind CSS, Blade.
- **Acuan Visual:** Figma Make Desa Kertajaya (Admin & Guest).

## 2. Roadmap Produk (Sprint & Status)
- **Sprint 0-5:** Selesai (Mencakup Setup, Auth, MFA, Audit Log, Media Library, CMS Halaman, Menu, Berita, Galeri, Dokumen, Kontak, Search, Maps).
- **Sprint 6 (UI Redesign & Stabilization):** Selesai (Penyelarasan visual seluruh fitur Admin & Publik).
- **Sprint 7 (Final Audit & Deployment):** *In Progress* (Regression test, optimasi infrastruktur, rilis via Shared Hosting + Cloudflare, Handover).

*Catatan: B1.0–B1.5 adalah jalur remediasi Admin UI dan recovery, bukan sprint produk.*

## 3. Database & Lingkungan Terisolasi
- **Local Working:** `village_cms_local_working_20260802` pada `127.0.0.1:5434`
- **Testing:** `village_cms_test_local_20260802` pada `127.0.0.1:5434` (Otomatisasi pengujian dilarang keras menggunakan database review/recovery).
- **Review:** `village_cms_review_20260727`

## 4. Batasan Logika Bisnis (Sesuai Data Dictionary & Activity Diagram)
- **Media:** File asli bersifat *private*. Seluruh unggahan wajib melewati pipeline watermark dan mencapai status *verified* sebelum dapat dipublikasikan atau digunakan di komponen konten.
- **Pesan Kontak:** Disimpan di `contact_messages` dengan pembatasan *rate limiting* dan memicu *Email Service* ke admin. Tidak ada balas pesan langsung dari CMS.
- **Audit Log:** Dibuat otomatis oleh sistem, tidak memiliki *updated_at*, dan dilarang keras dihapus secara manual oleh Admin (hanya melalui *scheduler* retensi).
- **Penghapusan Konten:** Menggunakan *soft delete* untuk halaman, berita, galeri, lokasi, dan dokumen agar tautan media terkait tidak rusak.

