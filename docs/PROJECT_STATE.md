# Village Website CMS Desa Kertajaya

## Workspace Aktif
- Path: `C:\Users\givar\KULIAH\WEB_KKN\village-cms-next`
- Branch: `development/next-sprint`
- HEAD: `a718cac`

## Roadmap Produk
- Sprint 0 Setup: selesai
- Sprint 1 Foundation/Auth/Settings/Audit: selesai
- Sprint 2 Media Library/Watermark: inti selesai, invisible watermark robust terbatas
- Sprint 3 Page Builder/Menu: selesai secara fungsi
- Sprint 4 News/Gallery/Documents: selesai secara fungsi
- Sprint 5A Contact Messages: selesai
- Sprint 5B Search: selesai
- Sprint 5C Maps: belum
- Sprint 6 Stabilization/SEO/Deployment: belum

Tegaskan:
B1.0–B1.5 adalah jalur remediasi Admin UI dan recovery, bukan sprint produk.

## Implementasi Contact Saat Ini
Fitur Pesan Kontak telah diimplementasikan dalam bentuk *Filament Resource*. Pengguna publik dapat mengirim pesan melalui _form_ publik yang kemudian divalidasi. Admin dapat melihat daftar pesan (*inbox*) dan melihat detail lengkap, serta memindahkannya ke *archive* (diarsipkan dan dapat dipulihkan tanpa penghapusan permanen). UI detail pesan sudah disesuaikan agar isi pesan tampil secara alami, rata kiri (bukan terpusat), dan mempertahankan pemformatan baris baru tanpa mengizinkan eksekusi raw HTML.

## Known Blocker
- PostgreSQL lokal port 5433 harus berjalan sebelum browser review.

## Environment Database
- **Development/Recovery**: `village_cms_recovery_20260723`
- **Testing**: `village_cms_test_20260724`
- **Review**: `village_cms_review_20260727`

## Aturan Kerja
- village-cms-next adalah workspace aktif.
- Jangan membuat worktree atau backup baru.
- Jangan menjalankan migrate:fresh, migrate:refresh, db:wipe, atau seeding pada database review/recovery.
- Akun dummy review tetap digunakan selama development.
- Jangan mengubah password, TOTP, recovery code, atau provisioning akun review.
- Rotasi akun dilakukan sekali setelah seluruh development selesai.
- Satu batch implementasi, satu visual review, maksimal satu corrective pass.
- Jangan stage atau commit tanpa persetujuan pengguna.

## Current Scope
Sprint 5C Maps MVP

## Visual/Content Debt (Sprint 6)
- Data kontak publik masih menggunakan nilai dummy (alamat, email, telepon).
- Konsistensi footer belum diselaraskan dengan Figma Make.
- Brand icon publik (header) masih menggunakan placeholder SVG, belum final.

## Next Actions
1. Maps.
2. Sprint 6 stabilization.

## Last Completed
Public Search MVP dengan pencarian Page, News, dan Document.
Full suite: 219 passed (797 assertions).
Build: PASS.
