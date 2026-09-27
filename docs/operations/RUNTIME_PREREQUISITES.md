# Kontrak prasyarat runtime

Sumber: `composer.json`/lockfile, `package.json`/lockfile, `config/*`, `routes/console.php`, job dan service aktif. Nilai di bawah adalah kontrak source; instalasi produksi, binary, kapasitas, proxy, dan TLS **belum diverifikasi**.

| Komponen | Kategori | Kebutuhan dan bukti source |
|---|---|---|
| PHP 8.3+ dan Composer | REQUIRED | `composer.json` mensyaratkan `^8.3`; `composer install` dari lockfile. |
| PostgreSQL dan `pdo_pgsql` | REQUIRED | Database bisnis, transaksi, trigger/lock, queue/session/cache default. Jangan memakai SQLite untuk klaim integrasi PostgreSQL. |
| `mbstring`, XML/DOM, `fileinfo`, GD, ZIP/`ZipArchive`, cURL/OpenSSL | REQUIRED / FORMAT-SPECIFIC | Laravel/Filament serta validasi gambar, PDF, Word/Excel, HTTP Turnstile. Periksa extension terpasang pada runtime target. Parser PDF (`smalot/pdfparser`), TCPDF, PEL, dan Intervention Image berasal dari Composer. |
| Node dan npm | BUILD-TIME ONLY | Vite 8 yang terpasang meminta Node `^20.19.0 || >=22.12.0`; `npm ci` lalu `npm run build`. Node tidak harus melayani request PHP jika aset sudah dibangun. |
| PostgreSQL queue worker | PRODUCTION OPERATIONAL | `QUEUE_CONNECTION=database` adalah default source; CSV/XLSX P5 memerlukan worker pada profil tersebut. Caller `ProcessMediaJob` saat ini memakai `dispatchSync()`, sehingga media berjalan dalam request meskipun class mengimplementasikan `ShouldQueue`. Lihat [worker/scheduler](QUEUE_AND_SCHEDULER.md). |
| Scheduler Laravel | PRODUCTION OPERATIONAL | Dua task hourly: hapus ekspor yang memenuhi syarat dan prune token/aset preview. Tidak ada backup/purge audit/kontak/media berbasis umur yang dijadwalkan. |
| Filesystem lokal privat | REQUIRED | `storage/app/private` untuk original, derivative baru, dokumen, ekspor, preview sementara; proses web/worker butuh izin aman. `storage/app/public` tetap diperlukan untuk aset/legacy yang belum dipindahkan; akses statis legacy harus diblokir pada origin. |
| Session/cache backend | REQUIRED | Default source `database`; schema dan koneksi harus siap. Tes P6 pernah membuktikan profil disposable PostgreSQL, bukan profil produksi. |
| `heif-convert` dan decoder HEIC | FORMAT-SPECIFIC / UNVERIFIED | Dipanggil `ProcessMediaJob` untuk HEIC, dengan timeout terkonfigurasi. Ketiadaan binary harus gagal aman; keberhasilan HEIC produksi belum dibuktikan. Jangan menonaktifkan persyaratan format secara diam-diam. |
| `APP_KEY` | REQUIRED SECRET | Enkripsi TOTP/preview dan data Laravel. Generate hanya instalasi baru; pertahankan pada update/restore. |
| `WATERMARK_SIGNING_KEY` dan `INSTALLATION_ID` | REQUIRED IDENTITY | Watermark image mengikat metadata ke kunci dan instalasi. Config saat ini hanya memuat satu kunci aktif; histori kunci yang pernah dipakai harus dijaga di custody backup, tanpa mengklaim keyring runtime otomatis. |
| Turnstile site/secret dan hostname | REQUIRED UNTUK FORM KONTAK/LOGIN TERKAIT | Secret salah/kosong gagal tertutup. Verifikasi domain/host di target; jangan memakai bypass. |
| Mail transport | OPTIONAL / D10 PENDING | Kontak saat ini menyimpan inbox tanpa notifikasi email; `MAIL_*` bukan prasyarat alur tersebut. |
| HTTPS, origin TLS, proxy dan web root `/public` | PRODUCTION OPERATIONAL | Validasi `APP_URL`, deteksi scheme melalui proxy, cookie secure, HSTS, header, route download, dan deny legacy statis pada request target nyata. Tidak ada klaim produksi terverifikasi. |
| Disk/log monitoring | PRODUCTION OPERATIONAL | Media/ekspor/cache/log membutuhkan ruang dan izin; pantau job gagal, exception dan cleanup gagal. Retensi organisasi belum dipilih kecuali kebijakan manual audit log enam bulan. |

File `public/hot`, cache route/config workstation, `.env`, secret, data cluster tes, log, sesi dan berkas ekspor privat tidak boleh masuk paket rilis workstation. [DEPLOYMENT_GUIDE](../DEPLOYMENT_GUIDE.md) menjelaskan penyiapan target dan [RECOVERY](RECOVERY.md) menjelaskan satu unit pemulihan.
