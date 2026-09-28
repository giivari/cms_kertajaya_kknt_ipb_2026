# Worker queue dan scheduler

Ini adalah kontrak source, **bukan bukti worker/scheduler produksi berjalan**. Operator harus menetapkan pengelola proses, user file, restart, health check, dan kapasitas sesuai lingkungan. Jangan menjalankan worker produksi dari laptop.

## Worker

`config/queue.php` memakai `QUEUE_CONNECTION=database` secara default dan queue `default`. CSV/XLSX Admin P5 dijalankan melalui job persiapan, chunk (100 baris), pembuatan file, dan completion; PDF tabel saat ini dirender sinkron tetapi artefaknya tetap privat dan diverifikasi sebelum download. `ProcessMediaJob` mengimplementasikan `ShouldQueue`, memakai token percobaan dan `tries = 1`, tetapi semua caller media yang ditemukan saat P9 memakai `dispatchSync()`: create/edit/reprocess Filament dan `MediaProcessingService`. Pemrosesan media aktif berjalan dalam request; mengubah `QUEUE_CONNECTION` saja tidak memindahkannya ke worker. Worker yang berhenti menahan ekspor queued; jangan mengklaim media aktif bergantung pada worker. Kebutuhan background media pada NFR-031 masih perlu rekonsiliasi requirement.

Contoh manual **untuk lingkungan terisolasi yang sudah diverifikasi**, bukan nilai Supervisor/systemd final:

```bash
php artisan queue:work database --queue=default
php artisan queue:failed
php artisan queue:restart
```

Pengelola proses produksi harus menjaga worker hidup, menangani shutdown/restart setelah deploy, dan memastikan user worker dapat membaca/menulis `storage/app/private` serta log. Selaraskan `DB_QUEUE_RETRY_AFTER`, timeout worker, dan durasi job queued terpanjang; default database `retry_after=90` detik bukan nilai yang sudah diuji pada produksi. Konversi HEIC saat ini sinkron dan dapat diatur hingga 600 detik: periksa timeout PHP/web server; jika kelak diantrekan, tinjau juga retry/timeout worker. **Jangan menyalin default itu sebagai konfigurasi produksi yang telah disetujui.** Tinjau failed jobs dan status `pending`, `generating`, `failed`, `cleanup_failed`; retry hanya setelah penyebab, idempotensi, dan kepemilikan artefak ditinjau. Jangan menghapus direktori ekspor/media secara manual untuk 'memperbaiki' queue.

## Scheduler

`routes/console.php` mendaftarkan tepat dua task `hourly()->withoutOverlapping()`:

1. `AdminExportCleanupService::pruneExpired()` — hanya ekspor pada disk privat yang memenuhi state/umur source (completed atau failed lebih dari satu hari; `cleanup_failed` dapat dicoba lagi), dengan pengamanan path dan file. Gagal cleanup tetap terlihat sebagai state gagal.
2. `PreviewTokenStore::pruneExpired()` — hapus token kedaluwarsa dan aset preview sementara yang dapat diklasifikasi aman; file tak dikenal dipertahankan.

Pemicu scheduler Laravel lazimnya `php artisan schedule:run` dari pengatur jadwal host setiap menit; bentuk host-specific harus disetujui operator. `php artisan schedule:list` dapat dipakai untuk inspeksi. Jika scheduler berhenti, artefak yang seharusnya dibersihkan bertahan dan pemakaian disk meningkat. Ini **bukan** backup scheduler, audit-log purge, contact purge, atau media age purge. Pemeriksaan manual safe adalah melihat status/log dan `schedule:list`; menjalankan cleanup membutuhkan konfirmasi target/storage karena benar-benar menghapus artefak yang memenuhi syarat.

Pantau exception aplikasi, `failed_jobs`, queue depth/umur, waktu terakhir worker memproses job, waktu terakhir scheduler berjalan, status export/media, kegagalan cleanup, dan pemakaian disk. Atur log rotation menurut kebijakan operator; jangan menurunkan retensi kelas data lain dari keputusan manual audit log enam bulan.
