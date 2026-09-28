# Pemulihan satu instalasi Village CMS

Panduan ini untuk operator berwenang. **Pemulihan produksi belum pernah direhearsal dalam P8.** Rehearsal disposable di bagian akhir hanya membuktikan subset teknis. D09 kini disetujui: RPO maksimum 24 jam, RTO target 4 jam, backup terenkripsi off-host setiap hari, retensi 30 harian dan 12 bulanan, serta latihan pemulihan sedikitnya setiap triwulan. Ini kebijakan, bukan bukti infrastruktur produksi sudah berjalan.

## Unit pemulihan

Pulihkan sebagai satu unit yang cocok waktunya:

1. Dump PostgreSQL beserta schema/migrasi, record Admin, konten, relasi, setting, status media/dokumen/ekspor/preview.
2. Original media, derivative aktif dan generasi yang masih dibutuhkan, file dokumen, serta berkas privat terkait dalam `storage/app/private`; file legacy yang masih menjadi referensi di `storage/app/public` juga dibutuhkan sampai cutover yang aman. Sertakan artefak ekspor bila record yang dipulihkan masih menjanjikan download. Log/audit dan sesi mengikuti kebijakan pemulihan yang diputuskan pemilik.
3. `APP_KEY`, `WATERMARK_SIGNING_KEY` dan material historis untuk verifikasi file lama, `INSTALLATION_ID`, konfigurasi aplikasi/DB/Turnstile, serta konfigurasi rahasia lain melalui custody terpisah. Jangan menaruh nilainya di runbook atau dump laporan.
4. Commit/release, `composer.lock`, `package-lock.json`, manifest/build metadata, domain/`APP_URL`, sertifikat origin/TLS/proxy, definisi worker dan scheduler. Cache target dapat dibangun ulang; jangan bawa cache route/config workstation.

Hanya PostgreSQL meninggalkan record yang menunjuk file hilang. Hanya file meninggalkan metadata/status, referensi dan checksum yang tidak cocok. Kehilangan `APP_KEY` dapat membuat ciphertext TOTP/preview tak terbaca; mengganti watermark key atau `INSTALLATION_ID` dapat membuat metadata lama tidak terverifikasi. Source saat ini hanya memiliki satu kunci watermark aktif, bukan keyring otomatis; histori kunci harus dijaga oleh prosedur custody, dan rotasi memerlukan prosedur terpisah. Jangan membuat ID instalasi baru hanya karena domain berubah.

## Titik backup yang cukup konsisten

Transaksi PostgreSQL tidak otomatis membuat snapshot filesystem. Ambil database dan file dari jendela yang dikoordinasikan: hentikan/quiesce penulisan editor, worker, scheduler dan upload sesuai prosedur target, atau gunakan snapshot storage+DB yang benar-benar koheren dan diuji operator. Catat waktu, release, status queue, daftar artefak, dan checksum tanpa mencetak rahasia. File kandidat/pekerjaan yang belum commit dapat tertinggal; setelah restore rekonsiliasi secara konservatif, **jangan** menghapus file tak dikenal hanya karena umur. Mekanisme snapshot bergantung lingkungan dan belum dibuktikan produksi.

## Urutan recovery terkontrol

1. Deklarasikan insiden, otorisasi operator, target **baru/terisolasi**, checkpoint yang dipilih dan dampak RPO/RTO. Jangan menimpa instalasi kerja untuk uji coba. Verifikasi backup dan checksum; bila tidak pasti, berhenti.
2. Siapkan release yang kompatibel, PostgreSQL kosong khusus target, storage privat dengan izin, dan `.env`/material rahasia yang **cocok** dengan checkpoint. Jangan menjalankan `key:generate`, `village:install-id`, `admin:provision`, `migrate:fresh`, atau seed pada data yang dipulihkan.
3. Pulihkan database ke target kosong dengan tooling PostgreSQL sesuai format dump (`pg_restore` untuk custom archive), lalu pulihkan file ke root privat/legacy yang sesuai. Verifikasi jumlah migrasi, FK/relasi, path, ukuran dan checksum untuk sampel media/dokumen/ekspor. Jangan menjalankan migrasi tambahan sampai kompatibilitas release–schema ditinjau.
4. Bangun aset/cache **untuk target**; sambungkan origin HTTPS tanpa membuka URL legacy `/storage/media/**` dan `/storage/originals/**`. Uji `APP_URL`, cookie secure, proxy, Turnstile hostname, login/MFA dengan otorisasi, konten publik, dokumen/media terkontrol, dan status job.
5. Pulihkan worker dan scheduler hanya setelah DB/files/identity konsisten. Periksa pending/failed jobs sebelum retry agar pekerjaan lama tidak menimpa generasi baru; pantau logs dan cleanup gagal. Buka trafik hanya setelah gate keamanan dan data disetujui.

Untuk dump PostgreSQL format custom, template perintah operator adalah `pg_dump -Fc -f <arsip-baru> <db-sumber-yang-terverifikasi>` dan `pg_restore --list <arsip>` untuk inspeksi, lalu `pg_restore --exit-on-error --no-owner --no-privileges -d <db-target-kosong-yang-berbeda> <arsip>`. Isi host/port/user melalui mekanisme rahasia lingkungan, bukan argumen password atau dokumen. Operator wajib membuktikan nama database, root file, izin, dan checkpoint sebelum menjalankannya; template ini tidak memberi izin menimpa database yang sudah berisi data. Pemulihan file memakai salinan/arsip dari checkpoint yang sama dan verifikasi hash, bukan folder kerja yang kebetulan memiliki nama serupa.

Rollback **kode** pada schema lebih baru bukan recovery otomatis. Jangan mengandalkan `migrate:rollback` sebagai rutinitas. Jika release lama tidak kompatibel, gunakan checkpoint release+data yang cocok atau tetap pada release yang melindungi URL legacy. Aturan deny origin legacy tidak boleh dihapus saat rollback; lihat [cutover](MEDIA_LEGACY_CUTOVER.md).

## Admin/MFA

Model autentikasi adalah `App\Models\Admin`, **bukan** `User`. `admin:provision` hanya membuat Admin pertama pada database kosong dan bukan perintah reset akun yang sudah ada. Admin yang masih sah mengganti sandi melalui profil dengan sandi saat ini serta TOTP aktif. **D02: pemulihan MFA hanya oleh operator berwenang, tanpa recovery code atau endpoint publik.** Prosedur berikut hanya berlaku bila Admin masih mengetahui sandi akun; command **tidak** mereset sandi.

1. Verifikasi identitas pemilik akun dan otorisasi operator di luar aplikasi. Catat tiket, operator, waktu, alasan, target instalasi, dan hasil. Ambil checkpoint DB+file+key+config menurut unit pemulihan di atas; jangan menganggap reset MFA sebagai pengganti backup.
2. Pastikan release/migrasi terbaru telah diterapkan, target database dan `APP_ENV` benar, hanya satu Admin aktif **dan tidak ada Admin terhapus tambahan**, serta `SESSION_DRIVER=database` pada koneksi DB aplikasi yang sama. Command akan menolak kondisi akun/sesi yang tidak sesuai. Jangan menjalankannya pada database kerja yang salah atau salinan yang belum disetujui.
3. Di shell server yang dibatasi untuk operator, jalankan `php artisan admin:recover-mfa '<email-admin-yang-tepat>'`. Periksa email dan status MFA yang ditampilkan, lalu jawab konfirmasi eksplisit. Untuk otomasi yang telah disetujui secara terpisah, opsi `--force` tetap memerlukan email eksplisit dan melewati prompt; jangan memakainya sebagai prosedur rutin. Jangan menaruh sandi, TOTP, atau kunci pada argumen, log tiket, atau keluaran terminal.
4. Command mengosongkan secret MFA terenkripsi, menaikkan `mfa_recovery_version`, menghapus sesi database milik Admin, serta menulis event `admin_mfa_recovery` dalam satu transaksi. Versi sesi lama ditolak pada request admin berikutnya. Hash sandi, `force_password_change`, dan `password_changed_at` tidak diubah. Audit aplikasi mencatat target Admin dan sumber CLI dengan aktor `null`; identitas operator ditelusuri melalui tiket/akses shell, bukan direkayasa sebagai akun Admin.
5. Admin masuk menggunakan **sandi lama yang sah**, kemudian mengikuti halaman enrolmen MFA wajib Filament untuk memasangkan authenticator baru. Pastikan akses dashboard baru tersedia setelah enrolmen; perangkat/sesi lama harus gagal. Catat hasil tanpa merekam secret atau kode TOTP. Jika transaksi gagal, command tidak mencatat event sukses dan tidak boleh dilanjutkan dengan edit SQL langsung.

Command telah diuji pada PostgreSQL disposable dengan regresi auth penuh. Ketersediaan akses CLI operator, checkpoint, izin database/sesi, serta walkthrough di target tetap perlu diverifikasi saat release. Pemulihan sandi yang terlupa adalah prosedur berbeda dan belum diberikan oleh command ini.

## Kebijakan D09 yang disetujui — pelaksanaan target belum diverifikasi

Jalankan backup terenkripsi off-host paling lambat setiap 24 jam; pertahankan 30 titik harian dan 12 titik bulanan; latih restore lengkap setidaknya triwulanan terhadap RPO maksimum 24 jam dan target RTO 4 jam. Titik pemulihan harus mencakup unit database+seluruh file teracu+material kunci/identitas+konfigurasi+release+TLS+worker/scheduler di atas. Tentukan operator dan penyimpanan off-host pada deployment target, pantau keberhasilan backup, dan uji restore. Tidak ada job backup yang ditambahkan oleh keputusan ini. Retensi audit enam bulan tidak menentukan retensi pesan kontak/media.

## Bukti rehearsal disposable P8 — 25 September 2026

Guardrail `safe-test.ps1 -ValidateOnly` lulus untuk `APP_ENV=testing`, cluster loopback `127.0.0.1:5434` di `storage/testing/p3a-postgresql`, database sumber `village_cms_test`; database kerja/recovery tidak dipakai. Fixture sintetis (Admin tidak dapat login, Page, Website Setting, Media+Document PDF valid) dibuat pada database sumber disposable dan root `storage/testing/p8-rehearsal/source/private`. `pg_dump` custom archive serta ZIP file tes dipulihkan ke **database berbeda** `village_cms_p8_restore_test` dan root `storage/testing/p8-rehearsal/restored/private`. `pg_restore` lulus; target memiliki 28 migrasi serta satu record fixture untuk tiap Admin/Page/Setting/Document, join Document→Media benar, SHA-256 PDF yang dipulihkan cocok, dan `DocumentDeliveryService::resolve` membaca dokumen dari root target. Tidak ada media pengguna yang disalin.

Database sumber disposable sudah memiliki dua row Media tes lain sebelum fixture P8 ditambahkan. Dump memuat row tersebut, tetapi rehearsal file hanya menyalin fixture P8; karena itu bukti ini **hanya** membuktikan pemulihan fixture representatif, bukan kelengkapan seluruh file untuk semua row database sumber. Rehearsal juga tidak membuktikan snapshot DB+filesystem produksi, pemulihan secret custody, login/MFA setelah restore, hasil watermark dengan key historis, pekerjaan worker/scheduler, TLS/origin, ataupun target RPO/RTO. Target database, fixture P8, arsip, dan cluster tes dibersihkan/dihentikan setelah bukti dicatat; artefak sementara bukan backup organisasi.
