# 7. Panduan teknis dan pemeliharaan untuk IT desa

Gunakan akun operator yang berwenang dan catat tindakan. Panduan ini adalah ringkasan; langkah teknis yang menyentuh data/kunci ada di [deployment](../DEPLOYMENT_GUIDE.md), [recovery](../operations/RECOVERY.md), [worker/scheduler](../operations/QUEUE_AND_SCHEDULER.md), dan [status proyek](../PROJECT_STATE.md). Jangan menjalankan perintah reset berdasarkan contoh lama dari buku panduan ini.

## Akses Admin dan MFA

Model akun aktif adalah `Admin`, bukan `User`. `admin:provision` hanya untuk database kosong dan bukan reset sandi akun yang sudah ada. Perubahan sandi/email/username melalui profil meminta sandi saat ini dan TOTP aktif. Bila authenticator hilang tetapi sandi masih diketahui, operator berwenang dapat memakai `admin:recover-mfa` setelah verifikasi identitas, otorisasi, tiket, dan checkpoint. Command ini mereset **MFA saja**, mencabut sesi, lalu Admin masuk kembali dengan sandi lama dan wajib mendaftar MFA baru. Ikuti [prosedur lengkap](../operations/RECOVERY.md); jangan mengedit kolom produksi secara bebas atau memakai `--force` tanpa persetujuan operasi yang tercatat.

## Gangguan aplikasi

Catat waktu, URL, pesan error tanpa rahasia, release, status worker/scheduler, ruang disk, dan log aplikasi sebelum tindakan. Kegagalan dokumen/media dapat berasal dari file hilang, checksum/format, status publikasi, atau job; jangan mengubah status menjadi verified secara manual. Kegagalan ekspor dapat meninggalkan state pending/generating/failed/cleanup_failed; lihat [operasi ekspor](../operations/EXPORTS.md). Cache route lama yang menyebut `ListMenus` harus diregenerasi pada target yang benar saat deploy; jangan mengedit file cache generated.

## Backup dan restore

Backup PostgreSQL saja **tidak cukup**. Database, original/derivative/dokumen, `APP_KEY`, kunci watermark dan histori yang dibutuhkan, `INSTALLATION_ID`, konfigurasi, release/lockfile, TLS/origin, serta definisi worker/scheduler harus cocok pada satu titik pemulihan. Lihat [RECOVERY](../operations/RECOVERY.md). D09 menetapkan RPO maksimum 24 jam, RTO target 4 jam, backup terenkripsi off-host harian, 30 titik harian dan 12 bulanan, serta rehearsal triwulanan. Pelaksanaan target masih perlu bukti. D08 enam bulan hanya berlaku bagi audit log.

## Operasi berkala

Pantau exception, auth/security failures yang tersedia, job gagal/tertahan, cleanup ekspor/preview, pemrosesan media, permission dan kapasitas disk. Scheduler source hanya menjalankan prune ekspor dan token preview tiap jam; tidak melakukan backup atau purge umum. Cutover URL legacy `/storage/media/**` dan `/storage/originals/**` masih wajib di origin sebelum rilis dapat disebut aman; lihat [MEDIA_LEGACY_CUTOVER](../operations/MEDIA_LEGACY_CUTOVER.md). Jangan membuka kembali aturan deny itu saat rollback.
