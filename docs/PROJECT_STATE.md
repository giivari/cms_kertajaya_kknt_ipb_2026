# Village Website CMS Desa Kertajaya

## Workspace Aktif
- Path: `C:\Users\givar\KULIAH\WEB_KKN\village-cms-next`
- Branch: `development/next-sprint`
- HEAD: `1fc50e2`

## Roadmap Produk
- Sprint 0 Setup: selesai
- Sprint 1 Foundation/Auth/Settings/Audit: selesai
- Sprint 2 Media Library/Watermark: inti selesai, invisible watermark robust terbatas
- Sprint 3 Page Builder/Menu: selesai secara fungsi
- Sprint 4 News/Gallery/Documents: selesai secara fungsi
- Sprint 5A Contact Messages: selesai
- Sprint 5B Search: selesai
- Sprint 5C Maps: in progress
- Sprint 6 Stabilization/SEO/Deployment: belum

Tegaskan:
B1.0–B1.5 adalah jalur remediasi Admin UI dan recovery, bukan sprint produk.

## Implementasi Contact Saat Ini
Fitur Pesan Kontak telah diimplementasikan dalam bentuk *Filament Resource*. Pengguna publik dapat mengirim pesan melalui _form_ publik yang kemudian divalidasi. Admin dapat melihat daftar pesan (*inbox*) dan melihat detail lengkap, serta memindahkannya ke *archive* (diarsipkan dan dapat dipulihkan tanpa penghapusan permanen). UI detail pesan sudah disesuaikan agar isi pesan tampil secara alami, rata kiri (bukan terpusat), dan mempertahankan pemformatan baris baru tanpa mengizinkan eksekusi raw HTML.

## Known Blocker
- Batch 3C Global Table Export menunggu validasi browser desktop/mobile serta pengujian alur queue dan download oleh Givari.

## Environment Database
- **Local Working**: `village_cms_local_working_20260802` pada `127.0.0.1:5434`
- **Recovery Cluster**: `C:\Users\givar\KULIAH\WEB_KKN\village-cms-db-recovery-20260801\data`
- **Testing**: `village_cms_test_local_20260802` pada `127.0.0.1:5434`
- **Review**: `village_cms_review_20260727`

### Status Recovery Lokal 2026-08-02
- Database kerja dibuat sebagai clone terisolasi dari kandidat recovery-live; database kandidat tetap read-only.
- Empat migration pending untuk Contact Messages, Maps, dan Preview Token telah dijalankan hanya pada database kerja.
- Enam Contact Messages, satu Kategori Lokasi aktif, dan satu Lokasi terbit direkonsiliasi dari kandidat review; `preview_tokens` tetap kosong.
- Schema final memiliki 24 migration dan 28 tabel dengan constraint, index, serta sequence tervalidasi sehat.
- Checkpoint custom-format bertimestamp tersedia di direktori `village-cms-db-recovery-20260801\working-backup`.
- Startup lokal satu klik didokumentasikan di `docs/LOCAL_DEVELOPMENT.md`; Batch 3A mulai dikerjakan tanpa mengubah database.

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
Admin Redesign Batch 3C — Global Table Export dan penghapusan Column Manager. Sprint 5C Maps tetap in progress dan tidak diubah pada batch ini.

### Status Preview dan Admin UX Reset
- Preview Phase 3 automated-green.
- Manual browser acceptance Preview gagal; UI Preview disembunyikan sementara.
- Backend Preview Phase 1–3, termasuk token, route, renderer, dan security headers tetap dipertahankan.
- Targeted suite enam file terakhir hijau: 45 tests, 248 assertions.
- Fungsi sidebar, dark mode, mobile drawer, dan Preview shutdown sudah lolos validasi browser.
- Admin Redesign v2 shell/dashboard diterapkan berdasarkan source Figma Make aktual dan video 2026-08-01.
- Batch 1 Admin Shell & Dashboard telah diterima untuk fungsi dan visual utama; alignment sidebar collapsed telah dipoles setelah baseline otomatis.
- Screenshot browser daftar, tambah, dan edit Berita/Halaman tetap wajib dibandingkan sebelum Batch 2 dapat dinyatakan visual-green.
- Baseline otomatis terakhir setelah Batch 1: 52 tests passed, 288 assertions.
- Batch 2 Admin Konten Utama sedang diimplementasikan untuk Berita, Halaman, kategori Berita kontekstual, dan workflow publikasi.
- Functional regression Batch 2 hijau: 110 tests, 467 assertions.
- Visual acceptance pertama Batch 2 gagal karena batas field terlalu lemah, grid form membuang ruang, toolbar tabel belum simetris, toolbar Rich Editor mobile belum rapi, status awal Berita tampil Terbit, dan toggle unggulan keliru ditandai wajib.
- Correction pass merapikan kontras field/section, dua kolom independen, toolbar dan empty state, Rich Editor mobile, serta menyelaraskan state awal Berita ke Draf tanpa mengubah lifecycle.
- Final visual correction Batch 2 telah diimplementasikan: tabel mobile memakai kolom esensial dan menu aksi resmi Filament, thumbnail Berita memiliki fallback aman, toolbar dirapikan, dan modal kategori menegaskan aksi utama.
- Form action memakai footer semi-sticky yang tetap berada dalam alur halaman; Page Builder hanya menerima polish border, header, action, dan overflow tanpa perubahan layanan maupun data.
- Status visual-green tetap menunggu screenshot browser baru pada daftar, tambah, dan edit Berita/Halaman di light/dark serta desktop/mobile.
- Batch 3A memoles alignment select pada form konten, toggle desktop sidebar melalui alias ikon resmi Filament, motion dengan reduced-motion, serta tampilan login/MFA tanpa mengubah alur keamanan.
- Typo huruf berlebih pada label OTP ditangani melalui override terjemahan project-owned; komponen `OneTimeCodeInput` resmi tetap digunakan.
- Visual-green Batch 3A belum diklaim sampai login, OTP, sidebar, tooltip, drawer mobile, select, dan reduced-motion diperiksa di browser.
- Batch 3B menerapkan tata kelola Album Galeri dan Perpustakaan Media yang responsif dengan menu aksi resmi Filament, fallback thumbnail lokal, serta form album dua kolom independen.
- Baseline sebelum final visual correction Batch 3B hijau: targeted 30 tests/142 assertions, regression 77 tests/402 assertions, dan safe-build berhasil.
- Pemilihan sampul dan item album tetap dibatasi pada gambar yang selesai diproses dan terverifikasi; original media tetap privat, sedangkan thumbnail admin hanya memakai derivative publik yang lolos verifikasi.
- Workflow upload tetap menerima JPEG, PNG, WebP, dan PDF maksimal 10 MB, menyimpan original pada disk privat, lalu meneruskan pemrosesan, watermark, dan verifikasi melalui queue yang sudah ada.
- Final visual correction Batch 3B memakai kontrak toolbar bersama hanya untuk Berita, Halaman, Album Galeri, dan Media; filter tanpa pilihan aktif tidak lagi merender badge nol, sedangkan badge aktif tetap menunjukkan jumlah filter.
- Daftar Album memakai kolom komposit khusus mobile untuk sampul, judul, dan jumlah foto; desktop tetap memakai kolom terpisah tanpa menduplikasi jumlah foto.
- Repeater Foto Galeri dimulai kosong sesuai kontrak album tanpa item dan menampilkan empty state serta tombol Tambah Foto. Form unggah Media memakai grid desktop 3:2 dengan dropzone resmi Filament yang diberi hierarki ikon secara visual.
- Visual-green Batch 3B belum diklaim sampai daftar/form Galeri dan Media diperiksa pada desktop/mobile serta light/dark mode.
- Batch 3C menghapus Column Manager dari 12 tabel admin aktual. Kolom desain tetap tampil pada desktop dan visibilitas responsif tetap membatasi metadata sekunder pada layar kecil.
- Toolbar tabel memakai pola Pencarian, Filter, dan satu menu Ekspor. CSV/XLSX memakai exporter resmi Filament dengan allowlist per resource, batas 10.000 baris, query tabel aktif, owner Admin, serta disk privat.
- PDF memakai TCPDF yang sudah tersedia, tanpa remote asset atau JavaScript, dengan batas 1.000 baris. Nilai spreadsheet dilindungi dari formula injection dan field rahasia/path privat tidak masuk allowlist.
- Infrastruktur ekspor menambah migration `exports` ber-FK UUID ke `admins` dan `notifications` ber-data JSON untuk PostgreSQL. Scheduler memangkas hanya direktori ekspor yang berumur lebih dari 24 jam.
- Checkpoint pramigration Batch 3C tersimpan di `village-cms-db-recovery-20260801\working-backup\batch-3c-pre-migration\village-cms-local-working-pre-batch3c-20260802-203426.dump` dengan SHA-256 `15B54BD776116B82083CB8DA67C16E9221DB09D486AAF2A445F0A13028728600`.
- Migration Batch 3C telah diterapkan hanya ke database kerja pada 2026-08-02. Schema kini memiliki 25 migration dan 30 tabel; `exports`, `notifications`, serta `job_batches` tersedia, sementara data bisnis tetap tidak berubah.
- Targeted Batch 3C hijau: 17 tests/489 assertions; regression hijau: 133 tests/684 assertions; safe-build hijau.
- CSV/XLSX tetap memerlukan queue worker dan cleanup ekspor 24 jam memerlukan scheduler. Visual-green Batch 3C belum diklaim sebelum browser validation dilakukan Givari.
- Browser correction Batch 3C memindahkan PDF dari return value binary action Livewire ke file sementara pada disk privat. Action kini hanya mengirim redirect UTF-8 aman ke route download bertanda tangan, dengan verifikasi pemilik Admin, TTL/cleanup 24 jam, dan header `nosniff`.
- Toolbar tabel memakai class project-owned langsung pada trigger Filter dan wrapper dropdown Ekspor; spacing 8px tidak lagi bergantung pada wrapper vendor yang memisahkan Search/Filter dari toolbar actions. Validasi browser ulang masih wajib.
- Preview UI tetap dinonaktifkan. Preview v2 belum dimulai dan dijadwalkan pada Batch 9.
- Maps tidak diubah dalam Batch 2, Batch 3A, maupun Batch 3B.
- Redesign website guest belum dimulai dan dijadwalkan pada Batch 6-8.

## Visual/Content Debt (Sprint 6)
- Data kontak publik masih menggunakan nilai dummy (alamat, email, telepon).
- Konsistensi footer belum diselaraskan dengan Figma Make.
- Brand icon publik (header) masih menggunakan placeholder SVG, belum final.

## Next Actions
1. Jalankan worker queue dan scheduler pada terminal terpisah selama validasi ekspor lokal.
2. Lakukan browser validation toolbar serta CSV/XLSX/PDF pada desktop/mobile, termasuk filter, hasil kosong, notifikasi, dan download privat.
3. Jangan lanjut ke Documents redesign atau batch berikutnya sebelum Batch 3C divalidasi Givari.

## Last Completed
- Sprint 5B Public Search
- Commit: `1fc50e2`
- Full suite: 219 passed (797 assertions)
- Build: PASS
