# Deployment Village CMS — prosedur terpisah

Ini menggantikan playbook lama yang mencampur instalasi dan redeploy. **Belum ada deployment produksi yang diverifikasi oleh remediasi ini.** Isi placeholder lingkungan harus disetujui operator; jangan menyalin nilai mesin pengembang. Baca [prasyarat runtime](operations/RUNTIME_PREREQUISITES.md), [recovery](operations/RECOVERY.md), dan [status](PROJECT_STATE.md) dahulu.

## Praoperasi untuk setiap perubahan

1. Identifikasi release, branch/commit, pemilik perubahan, database tujuan, root file, worker/scheduler, dan origin/TLS. Pastikan web root adalah `<release>/public` dan PostgreSQL tidak dibuka ke internet untuk kenyamanan alat desktop.
2. Siapkan checkpoint **database + file + identitas konfigurasi/kunci** pada titik yang cukup konsisten menurut [RECOVERY](operations/RECOVERY.md). D09 kini menetapkan RPO maksimum 24 jam, RTO target 4 jam, backup terenkripsi off-host setiap hari, retensi 30 titik harian dan 12 bulanan, serta rehearsal sedikitnya triwulanan. Jangan menganggap checkpoint lokal ini membuktikan kebijakan target sudah berjalan; verifikasi backup dan restore pada infrastruktur target tetap wajib.
3. Pastikan paket rilis tidak membawa `.env`, kunci, `public/hot`, `storage/testing`, cluster PostgreSQL lokal, log/sesi, ekspor privat, aset preview sementara, `.guardrails.local.json`, atau `bootstrap/cache` dari workstation. Buat cache target di target, bukan menyalin cache lama.
4. Verifikasi extension PHP, PostgreSQL, izin `storage`/`bootstrap/cache`, `public/build/manifest.json`, queue, scheduler, dan jalur layanan media/dokumen. Cek [cutover media legacy](operations/MEDIA_LEGACY_CUTOVER.md); source deployment saja **tidak** menutup bypass URL statis.

Pada paket baru, buat direktori runtime yang kosong sebelum menjalankan Composer/Artisan: `storage/app/private`, `storage/app/public`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, `storage/logs`, dan `bootstrap/cache`. Manifest file tidak membawa direktori kosong; tanpa `storage/framework/views`, `composer install` dapat gagal saat `package:discover`. Berikan izin tulis hanya kepada proses yang memerlukannya. Build Vite saat ini juga memerlukan HTTPS egress terkontrol untuk font yang dikonfigurasi melalui plugin Bunny.

## Fresh install — hanya instalasi kosong

Prasyarat: database baru benar-benar kosong, tidak ada identitas/berkas yang perlu dipertahankan, dan operator telah menyetujui domain/TLS serta penempatan rahasia. Perintah berikut adalah **contoh pada target kosong**, bukan skrip otomatis yang boleh dijalankan pada update.

```bash
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
cp .env.example .env
# Isi APP_ENV=production, APP_DEBUG=false, APP_URL, PostgreSQL, session/cache/queue,
# Turnstile, WATERMARK_SIGNING_KEY, dan konfigurasi lain melalui kanal rahasia.
php artisan key:generate
php artisan village:install-id
php artisan migrate --force
php artisan admin:provision
```

`admin:provision` hanya untuk Admin pertama dan dapat menampilkan password acak sekali di terminal; tangani output melalui prosedur custody yang sah. `village:install-id` **mengubah `.env`** dan hanya boleh dipakai saat identitas instalasi baru ditetapkan. Sediakan `WATERMARK_SIGNING_KEY` secara aman; `.env.example` sengaja kosong untuk kunci itu. `storage:link` hanya bila dibutuhkan aset publik yang memang diizinkan, dengan deny origin untuk namespace media legacy sebelum rilis. Jangan menganggap symlink sebagai pengganti route media terkontrol.

Setelah izin file, origin HTTPS, worker, dan scheduler disiapkan, bangun cache **di lingkungan target** sesuai bagian Cache di bawah. Verifikasi login/MFA, halaman publik, route media/dokumen, ekspor, log, serta deny URL legacy sebelum membuka trafik. Kesiapan rilis tetap menunggu gate pada `PROJECT_STATE.md`.

## Update — instalasi berisi data

Pertahankan database, `storage/app/private`, file legacy yang masih diperlukan, akun Admin, `.env`, `APP_KEY`, `WATERMARK_SIGNING_KEY` beserta material historis yang diperlukan, `INSTALLATION_ID`, dan rahasia lain. Domain boleh berubah tanpa membuat identitas instalasi baru. Jangan menjalankan Composer `setup`, `post-create-project-cmd`, `key:generate`, `village:install-id`, seeder, atau `migrate:fresh` sebagai langkah rutin update.

1. Ambil checkpoint konsisten dan uji keterbacaan artefaknya. Bandingkan kebutuhan migrasi dengan release lama dan pastikan aplikasi lama/baru kompatibel atau rencanakan maintenance mode (`php artisan down` / `php artisan up`) pada target yang disetujui.
2. Pasang kode/dependensi dari lockfile dan bangun aset pada lingkungan build yang sesuai. Jangan menimpa `.env` atau root data. Tinjau migrasi aditif, lalu pada **database target yang sudah dipastikan identitasnya** jalankan `php artisan migrate --force` sekali dengan pencatatan hasil.
3. Regenerasi cache route/config/view dari release dan konfigurasi target. **Cache route normal lama masih tercatat mengandung `ListMenus`**, sehingga jangan mengirim atau memakai artefak workstation itu. Jangan mengedit file cache secara manual.
4. Restart worker dengan `php artisan queue:restart` setelah proses baru tersedia; pengelola proses harus menaikkan worker kembali. Pastikan scheduler tetap berjalan dan check health/log/failed jobs.
5. Uji route/menu, download terkontrol, preview, ekspor, serta deny origin media legacy. Jangan membuka kembali `/storage/media/**` atau `/storage/originals/**` pada update maupun rollback.

## Rollback kode berbeda dari recovery data

Rollback kode hanya aman bila release lama dapat membaca schema, status, dan generasi file terbaru **serta** mempertahankan route akses terkontrol. Jangan otomatis menjalankan `migrate:rollback`, menghapus generasi media baru, atau memulihkan aturan origin yang mengekspos URL legacy. Bila tidak kompatibel, pertahankan release terkontrol dalam maintenance response sambil memakai checkpoint release/data yang cocok menurut [RECOVERY](operations/RECOVERY.md). Data recovery adalah operasi terpisah yang mengganti satu unit pemulihan, bukan efek samping pergantian kode.

## Recovery / disaster restore

Gunakan hanya [RECOVERY.md](operations/RECOVERY.md). Pemulihan PostgreSQL saja tidak mengembalikan original, derivative, dokumen, `APP_KEY`, kunci watermark, atau identitas instalasi.

## Build, cache, jaringan, dan keamanan

- Vite 8 pada dependency terpasang meminta Node `^20.19.0 || >=22.12.0`; `npm ci` memakai lockfile. Periksa `public/build/manifest.json` dari release target. `public/hot` adalah artefak dev dan harus absen pada rilis.
- Setelah source dan `.env` target siap, operator dapat memakai `php artisan optimize:clear`, `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` **di target**, lalu menguji route terdaftar. Perintah ini tidak dijalankan pada repository kerja dalam P8. Jika route cache gagal, hentikan deploy; jangan hidupkan kembali `ListMenus` lama.
- Origin harus melayani HTTPS end-to-end; mode proxy seperti Cloudflare Full (Strict) memerlukan sertifikat origin yang valid. `APP_URL`, host Turnstile, secure cookie, deteksi scheme di balik proxy, dan header HSTS harus diverifikasi pada request HTTPS nyata. CSP umum masih report-only; response tertentu punya CSP enforcement sendiri. Jangan mengklaim TLS/proxy produksi telah diperiksa dari dokumen ini.
- `public/storage` tidak boleh membuka original/derivative terkelola melalui jalur statis. Terapkan dan verifikasi aturan deny di [MEDIA_LEGACY_CUTOVER](operations/MEDIA_LEGACY_CUTOVER.md) tanpa memblokir aset publik lain. File yang telah di-cache pihak ketiga memerlukan kebijakan purge di penyedia bila dibutuhkan.
- Pantau exception aplikasi, kegagalan auth, pemrosesan media, export/queue, scheduler, disk penuh dan permission. Jalankan kebijakan backup D09 sesuai [RECOVERY](operations/RECOVERY.md); retensi manual enam bulan khusus audit log tidak menjadi izin purge otomatis media, kontak, atau data CMS lain.
