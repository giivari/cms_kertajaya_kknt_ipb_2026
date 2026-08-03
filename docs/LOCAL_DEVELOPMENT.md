# Menjalankan Website Lokal

Seluruh layanan lokal dapat dinyalakan dari PowerShell tanpa membuka aplikasi Laragon.

## Menyalakan

Jalankan dari root proyek:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\start-local.ps1
```

Skrip akan memeriksa atau menyalakan PostgreSQL recovery pada port `5434`, memverifikasi database kerja, menjalankan Laravel pada port `8015`, menjalankan Vite, lalu membuka:

`http://127.0.0.1:8015/desa-dashboard`

## Mematikan

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\stop-local.ps1
```

Skrip stop hanya menghentikan Laravel dan Vite yang direkam oleh skrip start, kemudian menghentikan cluster PostgreSQL recovery secara clean. PostgreSQL Laragon pada port `5432` tidak disentuh.

## Konfigurasi Lokal

- PostgreSQL: `127.0.0.1:5434`
- Database aplikasi: `village_cms_local_working_20260802`
- Database test: `village_cms_test_local_20260802`
- Laravel: `127.0.0.1:8015`
- Vite: `127.0.0.1:5173`
- Data cluster: `C:\Users\givar\KULIAH\WEB_KKN\_archive\database-recovery\db-recovery-20260801\data`

Jangan menjalankan `initdb`, `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, atau seeder pada database kerja/recovery.

Seluruh test wajib dijalankan melalui `scripts/guardrails/safe-test.ps1`. Database test boleh dikosongkan atau dimigrasikan ulang oleh test. Database aplikasi dan seluruh database recovery/kandidat tidak boleh pernah menjadi target test.

## Ekspor Tabel Admin

Migration Batch 3C untuk tabel `exports` dan `notifications` telah diterapkan ke database kerja pada 2026-08-02. Checkpoint pramigration tersedia di:

`C:\Users\givar\KULIAH\WEB_KKN\_archive\database-recovery\db-recovery-20260801\working-backup\batch-3c-pre-migration\village-cms-local-working-pre-batch3c-20260802-203426.dump`

SHA-256: `15B54BD776116B82083CB8DA67C16E9221DB09D486AAF2A445F0A13028728600`

CSV dan Excel diproses melalui queue database. Saat mengembangkan fitur ekspor, pastikan worker queue berjalan; worker dapat dijalankan pada terminal terpisah:

```powershell
php artisan queue:work --queue=default --tries=3
```

File ekspor berada pada disk privat `local`, bukan `public/storage`. Scheduler Laravel perlu berjalan agar ekspor berumur lebih dari 24 jam dibersihkan:

```powershell
php artisan schedule:work
```

Jangan menghapus direktori media atau memakai wildcard untuk membersihkan ekspor. Cleanup project-owned hanya menangani direktori `filament_exports/{id}` yang terhubung ke record ekspor kedaluwarsa.

PDF dibuat sinkron saat action dipilih, lalu disimpan sementara di direktori privat `filament_exports/{id}`. Browser menerima redirect ke route bertanda tangan yang memverifikasi Admin pemilik; PDF tidak dikirim sebagai binary melalui response JSON Livewire. Scheduler yang sama menghapus file PDF sementara setelah 24 jam.

## Jika Start Gagal

Periksa berkas berikut di `storage/logs`:

- `local-postgresql.log`
- `local-laravel.out.log` dan `local-laravel.err.log`
- `local-vite.out.log` dan `local-vite.err.log`

Jika pesan menyebut port telah dipakai proses yang tidak direkam, hentikan proses tersebut secara sadar atau pilih port lain melalui perubahan terkontrol. Jangan menghapus `postmaster.pid`.
