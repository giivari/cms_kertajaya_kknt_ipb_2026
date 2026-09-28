# Checklist verifikasi release candidate Village CMS

Dokumen ini adalah gerbang operasi untuk release candidate, bukan izin deployment dan bukan bukti target sudah lolos. Status source saat dokumen disusun: **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS** dengan penundaan yang disetujui untuk NFR-031 (pemrosesan Media sinkron) dan NFR-033 (kompatibilitas versi algoritma/riwayat kunci). Baseline otomatis terakhir adalah **459 tes lulus, 0 gagal, 2.924 assertion, 573,83 detik**. Release tetap **VERIFICATION PENDING** sampai bukti target pada checklist ini tersedia.

Runbook kanonis yang dirujuk, dan tidak digandakan penuh di sini:

- [Deployment](../DEPLOYMENT_GUIDE.md)
- [Prasyarat runtime](RUNTIME_PREREQUISITES.md)
- [Worker dan scheduler](QUEUE_AND_SCHEDULER.md)
- [Cutover Media legacy](MEDIA_LEGACY_CUTOVER.md)
- [Recovery](RECOVERY.md)

Setiap hasil disimpan di lokasi bukti yang dikendalikan operator **di luar direktori release**. Jangan simpan rahasia, cookie, token, dump database, daftar file privat, atau keluaran inventory Media di repository.

## 1. Identitas dan komposisi release

Catat branch, commit penuh, waktu, operator, target, hash lockfile, dan metode pembuatan paket. Inventaris source saat review: 246 file `app`, 3 `bootstrap`, 15 `config`, 29 migrasi, 73 `resources`, 2 `routes`, 5 guardrail, `composer.lock`, `package-lock.json`, dan 6 runbook operasi sebelum checklist ini ditambahkan. Hash saat review:

- `composer.lock`: `63CAB0DE9544CEAB4AC4C9B7B232786848111689B97291A24F4A6E676D99FDA8`
- `package-lock.json`: `4C7C5D69F681E97A1F46392C21EE9053F8A91B9F32CF27E94CFD8A5E10A73246`

Paket release harus memuat source Laravel, seluruh migrasi, lockfile Composer/npm, resources frontend, route/config, command Artisan, guardrail yang diperlukan, dan runbook. `public/build` harus berasal dari build release yang baru, bukan manifest workstation yang kebetulan ada.

Jangan masukkan:

- `.env`, `.env.testing`, `.guardrails.local.json`, private key, secret handover, atau nilai custody;
- `storage/testing`, cluster PostgreSQL tes, database developer, dump/backup, log, session, cache, ekspor privat, dan aset preview sementara;
- isi runtime `storage/app/private` dari workstation;
- `public/hot`;
- cache route/config/view dari `bootstrap/cache` workstation;
- `node_modules`, artefak editor, atau output debug.

File `.gitignore` placeholder seperti `storage/logs/.gitignore` dan `storage/app/private/.gitignore` boleh ikut; data di direktori tersebut tidak boleh ikut. Simpan hasil `git status`, daftar paket, hash lockfile, dan daftar final artefak sebagai bukti.

## 2. State yang wajib dipertahankan saat update

Update harus mempertahankan database produksi, `storage/app/private`, storage legacy yang masih diperlukan selama transisi D07, akun dan kredensial Admin, `.env` target, `APP_KEY`, `WATERMARK_SIGNING_KEY` beserta material historis yang masih dibutuhkan, `INSTALLATION_ID`, domain/configuration, serta checkpoint recovery. Jangan menjalankan `key:generate`, `village:install-id`, `admin:provision`, seeder, `migrate:fresh`, `db:wipe`, atau rotasi kunci pada update.

## 3. Review kompatibilitas migrasi remediasi

Urutan filename memastikan empat migrasi remediasi berjalan setelah 25 migrasi schema sebelumnya, dengan D02 terakhir.

| Urutan | Migrasi | Perubahan maju | Existing-row/default | Risiko lock/kompatibilitas |
|---:|---|---|---|---|
| 26 | `2026_09_23_000001_add_media_processing_attempt_state.php` | Menambah tiga kolom nullable pada `media`. | Tidak perlu backfill; record lama tetap `NULL`. | `ALTER TABLE media`; lock DDL singkat, durasi tergantung ukuran/kegiatan tabel. PostgreSQL kompatibel. |
| 27 | `2026_09_23_000002_add_media_cleanup_and_reference_guards.php` | Menambah tiga kolom cleanup, satu index, fungsi PL/pgSQL, dan trigger pada enam tabel referensi. | Kolom nullable; tidak menormalkan data lama. | Pembuatan index dan trigger dapat menahan lock tabel. Memerlukan PostgreSQL/PL/pgSQL; non-PostgreSQL tidak mendapat trigger dan bukan target yang didukung. Jadwalkan maintenance/quiesce penulis. |
| 28 | `2026_09_25_000001_add_verified_export_lifecycle.php` | Menambah sembilan kolom pada `exports`; membuat `export_chunks` dengan FK cascade dan unique `(export_id,page)`. | `lifecycle_state` non-null default `pending`, sehingga row lama membaca status `pending`; kolom lain nullable. Tidak ada penghapusan data pada `up`. | `ALTER TABLE exports` dan pembuatan FK/index membutuhkan lock; tinjau row ekspor lama sebelum mengaktifkan worker. |
| 29 | `2026_09_26_000001_add_admin_mfa_recovery_version.php` | Menambah `admins.mfa_recovery_version` unsigned bigint non-null default `0`. | Semua Admin lama aman membaca versi `0`; tidak perlu backfill manual. | `ALTER TABLE admins`; tabel seharusnya sangat kecil karena single-admin, tetapi tetap jalankan pada window terkendali. Jangan menjalankan `down` sebagai rollback rutin. |

Semua `up()` bersifat additive. Ini adalah review statis, bukan bukti migrasi produksi. `down()` menghapus kolom/trigger/table dan bersifat destruktif; jangan gunakan sebagai strategi rollback default.

## 4. Matriks prasyarat target

| Komponen | Versi/kapabilitas yang diwajibkan source | Mengapa | Cara verifikasi | Gate |
|---|---|---|---|---|
| PHP | `^8.3` | Laravel 12, Filament 4, pipeline file. | `php --version`; `composer check-platform-reqs --no-dev`. | BLOCKING |
| Extension PHP | `pdo_pgsql`, bcmath, cURL, DOM/XML/XMLReader/libxml, fileinfo, GD, hash, iconv, intl, mbstring, OpenSSL, session, tokenizer, ZIP/ZipArchive, zlib | DB PostgreSQL, gambar, Office/PDF, MFA, Turnstile, ekspor. | `php -m`; `composer check-platform-reqs --no-dev`. | BLOCKING; HEIC terpisah |
| PostgreSQL | Versi tidak dipatok repository; wajib mendukung transaksi, JSONB, advisory lock, PL/pgSQL trigger, `timestamp with time zone`, dan database queue/session/cache. | Invariant dan konkurensi bergantung PostgreSQL. | `psql --version`; `SHOW server_version`; cek PL/pgSQL; jalankan migrasi pada clone target-like. | BLOCKING |
| Composer | Composer 2 yang kompatibel dengan lockfile; repository tidak mematok patch version. | Instal dependency PHP deterministik. | `composer --version`; `composer validate`; `composer install --dry-run --no-dev`; platform check harus lulus. | BLOCKING |
| Node/npm | Vite 8: Node `^20.19.0 || >=22.12.0`; npm harus mendukung lockfile v3. | Build asset saja. | `node --version`; `npm --version`; `npm ci`. | BLOCKING untuk build |
| Gambar | GD PHP untuk JPEG/PNG/WebP; permission staging privat. | Decode, resize, thumbnail, checksum final. | `php -r "exit(extension_loaded('gd') ? 0 : 1);"`; fixture nyata. | BLOCKING untuk Media image |
| HEIC | `heif-convert` dan `exiftool` tersedia bagi user web; genuine HEIC dapat didecode. | `ProcessMediaJob` memanggil keduanya; timeout 10–600 detik. | `heif-convert --version`; `exiftool -ver`; uji disposable. | BLOCKING jika HEIC diklaim didukung; selain itu gate pending eksplisit |
| PDF/Office | Composer parser PDF; `fileinfo`, DOM, ZipArchive; validator OLE internal untuk `.doc/.xls`. | Validasi bytes PDF/DOC/DOCX/XLS/XLSX. | `composer check-platform-reqs`; genuine fixtures melalui upload/download. | BLOCKING untuk format terkait |
| Filesystem | User web/worker dapat menulis `storage` dan `bootstrap/cache`, membaca private Media/Document, tanpa membuat source tree broadly writable. | Upload, derivative, preview, ekspor, log/cache. | Pemeriksaan owner/mode, write probe terkontrol, controlled download. | BLOCKING |
| Database queue | `QUEUE_CONNECTION=database`, tabel jobs/failed_jobs, worker queue `default`. | Ekspor CSV/XLSX asynchronous. Media tetap sinkron sesuai NFR-031. | `php artisan queue:failed`; enqueue ekspor disposable; pantau completion. | BLOCKING untuk ekspor |
| Scheduler | Host memanggil `php artisan schedule:run` tiap menit; tepat dua task hourly terdaftar. | Cleanup ekspor dan prune preview. | `php artisan schedule:list`; bukti last-run/log target. | BLOCKING operasional |
| TLS/proxy/cookie | Web root `/public`, HTTPS end-to-end, proxy trusted sesuai topologi, `APP_URL` HTTPS, cookie secure/HTTP-only/same-site sesuai kontrak. | Session/auth dan URL aman. | Request HTTPS nyata, header/cookie inspection, proxy/origin test. | BLOCKING |
| Turnstile | Site/secret dan hostname target benar; egress HTTPS tersedia; gagal tertutup. | Login/form terkait. | Uji token valid, invalid, missing config, dan network failure tanpa menampilkan secret. | BLOCKING |
| Logging/monitoring | Log writable dan diputar; exception, auth, Media, export, scheduler, permission/disk failure terlihat. | Deteksi kegagalan release. | Trigger kegagalan aman, periksa alert/log, kapasitas disk. | BLOCKING operasional |
| Backup off-host | Backup harian terenkripsi, retensi 30 harian+12 bulanan, rehearsal triwulanan, RPO ≤24h/RTO ≤4h. | D09. | Bukti job, checksum, enkripsi/custody, inventory restore point, laporan rehearsal. | BLOCKING release |

## 5. Prosedur build pada release copy terisolasi

Jalankan hanya pada clean checkout/copy yang tidak berbagi `public/build`, `.env`, atau storage dengan instalasi kerja.

Sebelum Composer menjalankan skrip Laravel, buat direktori runtime kosong dan atur izinnya pada release copy: `storage/app/private`, `storage/app/public`, `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, `storage/logs`, serta `bootstrap/cache`. File manifest tidak merepresentasikan direktori kosong. Build Vite dengan `bunny(...)` pada `vite.config.js` memerlukan HTTPS egress terkontrol ke penyedia font; jika egress tidak tersedia, hentikan gate build dan siapkan lingkungan build berizin tanpa mengubah asset aplikasi secara diam-diam.

```bash
git rev-parse HEAD
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
node --version
npm --version
npm ci
test ! -e public/hot
npm run build
test -f public/build/manifest.json
php -r '$m=json_decode(file_get_contents("public/build/manifest.json"),true,512,JSON_THROW_ON_ERROR); foreach($m as $v){if(isset($v["file"])&&!is_file("public/build/".$v["file"])) exit(1);} echo count($m),PHP_EOL;'
```

Periksa output `public/build` untuk `.env`, key/private file, dump, sourcemap yang tidak diinginkan, dan pola nama variable rahasia. Pencarian hanya boleh melaporkan nama file/match yang aman, bukan nilai secret. Simpan versi Node/npm, log `npm ci`, log build, hash manifest, daftar asset, dan hasil scan. Hentikan bila dependency tidak cocok lockfile, `public/hot` ada, build gagal, target manifest hilang, atau scan menunjukkan material sensitif.

Build nyata tidak dijalankan pada worktree ini karena Vite selalu menulis `public/build`; manifest workstation yang memiliki 15 entri dan tidak kehilangan target bukan bukti build release baru.

## 6. Prosedur migrasi target

1. Verifikasi identitas database, release commit, schema, storage root, dan operator tanpa mencetak credential.
2. Quiesce editor/upload/worker/scheduler atau masuk maintenance mode bila compatibility window memerlukannya: `php artisan down --render="errors::503"` hanya pada target berwenang.
3. Ambil checkpoint konsisten database+file+key/config; verifikasi arsip dapat dibaca dan simpan di luar release.
4. Rekam sebelum migrasi:

   ```bash
   php artisan migrate:status
   php artisan migrate --pretend --force
   ```

5. Tinjau SQL/lock dan pastikan empat migrasi di bagian 3 adalah yang pending. Hentikan bila ada migrasi tak dikenal atau target salah.
6. Jalankan sekali setelah backup disahkan:

   ```bash
   php artisan migrate --force
   php artisan migrate:status
   ```

7. Pastikan `2026_09_26_000001_add_admin_mfa_recovery_version` berstatus `Ran`, lalu lakukan smoke test. Jangan memakai `migrate:fresh`, `migrate:refresh`, `db:wipe`, atau `migrate:rollback` untuk update.
8. Keluar maintenance hanya setelah cache, worker, route, dan smoke test lulus: `php artisan up`.

Simpan backup ID/hash, status/pretend output, durasi migrasi, status akhir, log error, dan waktu maintenance.

## 7. Prosedur cache target

Cache dibuat dari release dan `.env` target, tidak disalin dari workstation.

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan route:list --json
```

Simpan `route:list` di evidence directory. Pastikan cache tidak memuat `ListMenus`, dan route representative tersedia: homepage, Page/News/Gallery, menu Filament, `media.derivative`, `admin.media.original`, `documents.download`, preview, dan download ekspor. Hentikan bila cache gagal dibangun, `ListMenus` muncul, atau route representative hilang. Jangan mengedit file cache generated.

## 8. Worker dan scheduler

Media tetap diproses sinkron sesuai keputusan NFR-031. Worker target dibutuhkan untuk ekspor queued.

```bash
php artisan queue:failed
php artisan queue:restart
php artisan schedule:list
```

Gunakan process manager target untuk memulai kembali `php artisan queue:work database --queue=default` dengan retry/timeout yang ditinjau terhadap job ekspor; jangan menganggap command foreground sebagai konfigurasi daemon final. Jalankan satu ekspor disposable dan buktikan state maju sampai completed, artefak privat dapat diunduh pemilik, failure terlihat, dan user worker memiliki permission. Pastikan hanya task `admin-exports:prune` dan `preview-tokens:prune` yang dijadwalkan hourly. `schedule:run` dapat menghapus artefak eligible; jalankan hanya pada target-like/target berwenang setelah storage dan data dipastikan. Tidak ada backup scheduler dalam aplikasi.

## 9. D07 cutover Media legacy

Ikuti [runbook D07](MEDIA_LEGACY_CUTOVER.md):

1. Ambil backup konsisten database, `storage/app/private`, `storage/app/public/media`, dan namespace original legacy yang ditemukan.
2. Pada instalasi berwenang, simpan output read-only `php artisan media:lifecycle-inventory --json` di evidence directory terbatas. Jangan menjalankan hanya dengan asumsi `--env=testing` mengisolasi public storage.
3. Uji controlled URL Media aktif, archived, unknown, dan admin original sebelum perubahan origin.
4. Pasang sebelum rule static/PHP umum:

   ```nginx
   location ^~ /storage/media/ { return 404; }
   location ^~ /storage/originals/ { return 404; }
   ```

5. Jalankan `nginx -t` atau pemeriksa sintaks origin yang benar untuk target. Reload dengan process manager target yang disetujui hanya bila sintaks lulus.
6. Uji HTTP baru: kedua namespace legacy `404`; controlled active route tetap berhasil; archived/unknown tetap ditolak; admin original tetap private; public asset lain tidak terblokir.
7. Purge namespace edge/CDN bila dipakai dan revocation segera dibutuhkan; simpan request/result purge.
8. Rollback aplikasi tidak boleh membuka kembali deny rule. Bila release lama tidak dapat melayani controlled Media, pertahankan release aman atau maintenance response sampai ada jalur terlindungi ekuivalen.

Jangan memindah atau menghapus byte legacy pada pass verifikasi release ini.

## 10. D02 readiness target

Deployment biasa hanya memeriksa keberadaan command dan migrasi, tanpa mereset Admin nyata:

```bash
php artisan migrate:status
php artisan list --raw | grep '^admin:recover-mfa'
php artisan admin:recover-mfa --help
```

Pastikan session target menggunakan database pada koneksi aplikasi dan prosedur operator/tiket/checkpoint tersedia. Drill destruktif hanya pada clone/recovery environment dengan satu Admin disposable: command interaktif harus menghapus MFA lama, mempertahankan password, mencabut sesi, menulis audit, mengharuskan enrollment normal, dan menolak sesi lama. Jangan memakai `--force` pada deployment biasa dan jangan menjalankan command terhadap Admin produksi sebagai smoke test.

## 11. D09 backup evidence

Sebelum release, operator harus menyediakan bukti berikut:

- job backup database dan seluruh file teracu berjalan paling lambat tiap 24 jam;
- hasil terenkripsi sebelum/selama transfer ke lokasi off-host, dengan custody kunci terpisah;
- 30 restore point harian dan 12 bulanan benar-benar dipertahankan;
- monitoring dan notifikasi kegagalan memiliki pemilik dan respons;
- checkpoint mencakup `APP_KEY`, watermark key/history yang diperlukan, `INSTALLATION_ID`, config, release/lockfile/build, domain/TLS, worker/scheduler;
- laporan restore penuh triwulanan menunjukkan RPO aktual ≤24 jam dan RTO aktual/terukur menuju target ≤4 jam.

Repository tidak menyediakan infrastruktur backup ini. Tanpa bukti target, status D09 adalah **operationally pending**.

## 12. Rehearsal recovery penuh

Gunakan target recovery baru, terisolasi, tanpa DNS/domain publik. Ambil restore point yang sama waktunya dan pulihkan berurutan:

1. release, lockfile, build metadata, dan konfigurasi target terisolasi;
2. database ke database kosong berbeda;
3. original Media, derivative yang diperlukan, Document/private files, legacy file teracu, ekspor yang masih dijanjikan;
4. `APP_KEY`, watermark material/history, `INSTALLATION_ID`, Turnstile dummy/recovery config, domain/TLS test;
5. permission, cache target, worker, dan scheduler definition.

Verifikasi Admin password/decryption dan MFA disposable, D02 recovery drill, Page/News/Gallery, publication schedule, controlled Media/original/Document, checksum derivative/thumbnail dan watermark, queue/export, scheduler listing/execution aman, log/monitoring, serta ketidaktersediaan domain produksi. Catat waktu setiap tahap, usia restore point, checksum, row/file reconciliation, RPO/RTO, dan hasil. Hentikan bila key/config tidak cocok, file teracu hilang, atau recovery harus terhubung ke domain/data produksi.

## 13. Matriks penerimaan browser

Jalankan pada target-like/target berwenang dengan data disposable. Ulangi skenario representative pada mobile, tablet, desktop; mouse dan keyboard; zoom 200% bila praktis.

| Skenario | Bukti wajib | Stop bila |
|---|---|---|
| Homepage + Featured Page/Gallery | D11, urutan, maksimum tiga Gallery, empty state, long title, no clipping. | Konten draft/future muncul atau layout tidak dapat digunakan. |
| Navigasi + footer/social | Primary/footer benar, mobile menu, safe URL, fokus terlihat. | Link private/unsafe atau keyboard terjebak. |
| Page/News/Gallery | Detail/list/pagination dan Media terkontrol. | Status/archive bypass atau overflow material. |
| Document download | Valid/denied/404, same-tab dan back/forward. | Loader global menetap atau authorization bocor. |
| Export download | Owner/completed/expiry dan failure state. | Loader menetap, artefak non-owner tersedia, atau failure tidak terlihat. |
| Map | Tab ke control/marker/link, Enter child link, Space jika semantik, pointer tetap bekerja. | Parent handler mencegah child link atau fokus tidak terlihat. |
| Preview | State terkini tampil tanpa menyimpan business data; modal/focus benar. | Preview memutasi data atau membuka asset private. |
| Login/MFA/profile | Turnstile, MFA setup/challenge, reauth, validation error, focus. | Bypass MFA, error tidak jelas, atau fokus terperangkap. |
| Loading/failure/empty | Loader halaman normal selesai; denied/network failure pulih. | Overlay menetap atau pengguna tidak dapat melanjutkan. |

F41 dinyatakan PASS hanya setelah download loader dan keyboard child-link Map benar-benar dijalankan di browser.

## 14. Matriks format genuine

| Format | Fixture | Jalur aktual | Expected evidence |
|---|---|---|---|
| DOC | File legacy `.doc` valid yang dibuat aplikasi Office/LibreOffice dan mempunyai stream Word CFB nyata; bukan header buatan. | Upload/edit Document → validasi `DocumentFilePolicy` → publish due → controlled download → archive/restore/delete. | Accepted, MIME/name/extension/checksum benar, download bytes/type cocok, fake/malformed ditolak, archive tidak public. |
| XLS | File legacy `.xls` valid dengan workbook BIFF/CFB nyata; bukan header buatan. | Jalur Document yang sama. | Accepted dan roundtrip controlled; malformed fake ditolak; publication/archive boundary benar. |
| HEIC | Genuine HEIC dari encoder/perangkat tepercaya plus fixture failure. | Upload Media → immutable original → `heif-convert`/orientation → optimize/watermark/checksum → activation → controlled route → failed reprocess. | Original unchanged, JPEG final valid, checksum/verification lulus, previous derivative bertahan saat failure. |

Simpan provenance fixture tanpa data pribadi, hash input/output, versi decoder, hasil HTTP, dan screenshot yang tidak memuat rahasia.

## 15. Stop conditions

Hentikan release bila backup konsisten tidak ada/tidak terbaca; custody `APP_KEY` atau watermark key meragukan; target database tidak pasti; pretend/actual migration gagal; build/manifest/secret scan gagal; route cache masih memuat `ListMenus`; worker ekspor wajib tidak tersedia; controlled Media rusak setelah deny D07; archived Media masih statically reachable; TLS/proxy/cookie/Turnstile tidak aman; permission/storage/log gagal; recovery penuh tidak mencapai confidence yang disetujui; atau muncul regresi browser/security material. Catat **SOURCE REOPEN REQUIRED** bila bukti menunjukkan defect source, tanpa memperbaiki pada deployment.

## 16. Rollback

**Code rollback** memilih release lama yang telah dibuktikan dapat membaca schema/file state baru dan tetap melayani Media melalui boundary terlindungi. Pertahankan `.env`, database, storage, key/identity, backup, dan deny D07. Jangan menjalankan migration `down` otomatis.

**Data restore** adalah operasi terpisah: gunakan checkpoint database+file+key/config+release yang koheren pada recovery procedure. Jangan mencampur database lama dengan file/key baru. Jika compatibility release lama belum terbukti, tetap di maintenance mode pada release aman dan lakukan recovery terkontrol. D07 rollback tidak boleh mengaktifkan kembali `/storage/media/**` atau `/storage/originals/**`.

## 17. Checklist eksekusi berurutan

### Aman dilakukan lokal sekarang

| # | Command/action | Expected | Stop condition | Evidence |
|---:|---|---|---|---|
| L1 | `git rev-parse HEAD` dan `git status --short --untracked-files=all` | Identitas dan dirty state tercatat; tidak ada staging baru oleh pass release. | Worktree/branch salah. | Commit/status snapshot. |
| L2 | `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot . -ValidateOnly` | Exit 0. | Guardrail gagal. | Exit/result. |
| L3 | `powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-build.ps1 -ValidateOnly` | Exit 0, tanpa build. | Prasyarat wrapper gagal. | Exit/result. |
| L4 | `composer check-platform-reqs --lock --no-dev` | Requirement lock dikenali; ini hanya bukti mesin lokal. | Lock invalid. | Output teredaksi. |
| L5 | Review 29 filename migrasi dan empat migrasi remediasi. | D02 terakhir; tidak ada migration tak dikenal. | Urutan/SQL ambigu. | Daftar migrasi/review. |
| L6 | `git diff --check` dan inspeksi exclusion tanpa membaca secret. | Tidak ada whitespace error; artefak terlarang tidak tracked. | Secret/data/test artifact masuk paket. | Log dan package manifest. |

### Memerlukan environment disposable/target-like

| # | Command/action | Expected | Stop condition | Evidence |
|---:|---|---|---|---|
| T1 | Clean release copy; `composer install ...`, `npm ci`, `npm run build`. | Lockfile honored, fresh manifest dan semua target ada. | Build/dependency/secret scan gagal. | Version/log/hash/list asset. |
| T2 | Restore clone target; `php artisan migrate --pretend --force`, lalu `php artisan migrate --force`. | 29 migrasi ran, termasuk D02; data fixture tetap. | Migration/lock/data reconciliation gagal. | Backup ID, SQL pretend, status/duration. |
| T3 | `optimize:clear`, `config:cache`, `route:cache`, `view:cache`, `route:list --json`. | Boot cached; route aktif ada; tidak ada `ListMenus`. | Cache/boot/route gagal. | Cache log dan route JSON. |
| T4 | Genuine DOC/XLS/HEIC matrix. | Actual application boundaries lulus. | Format valid ditolak atau fake diterima; lifecycle bocor. | Hash, HTTP, screenshot, decoder version. |
| T5 | Browser matrix mobile/tablet/desktop dan keyboard. | F41 dan flow final usable. | Material UX/security defect. | Browser/version, viewport, video/screenshot. |
| T6 | Queue/export dan scheduler dengan fixture disposable. | Export completed/private; dua task terdaftar; failure terlihat. | Job/permission/cleanup tidak aman. | Worker/job/schedule log. |
| T7 | Full recovery rehearsal termasuk drill D02 pada Admin disposable. | Data/file/key/config cocok; RPO/RTO terukur. | Decrypt/login/file/checksum gagal. | Rehearsal report. |

### Memerlukan akses target/produksi yang diotorisasi

| # | Command/action | Expected | Stop condition | Evidence |
|---:|---|---|---|---|
| P1 | Validasi runtime, filesystem, DB identity, TLS/proxy/cookie, Turnstile, logs/monitoring. | Semua blocking prerequisites lulus. | Salah target/secret/cookie/proxy/permission. | Checklist target teredaksi. |
| P2 | Verifikasi D09 dan ambil pre-migration checkpoint konsisten. | Backup encrypted/off-host/readable tersedia. | Backup/custody/retention tidak terbukti. | Backup job/restore-point/hash evidence. |
| P3 | Maintenance/quiesce; pretend; `php artisan migrate --force`; status. | Migrasi sukses sekali. | SQL/lock/migration error. | Migration logs. |
| P4 | Regenerasi cache target dan restart worker via process manager. | Cached boot dan export worker sehat. | `ListMenus`, route hilang, worker gagal. | Route JSON/health/log. |
| P5 | D02 readiness: status migration, `artisan list`, `--help`; **jangan reset Admin produksi**. | Command tersedia dan runbook/operator siap. | Migration/command/procedure tidak ada. | Output help/status. |
| P6 | Inventory D07 → backup → controlled precheck → origin deny → syntax/reload → HTTP/CDN verification. | Static legacy URL 404; controlled active tetap bekerja. | Backup tidak ada, syntax gagal, controlled route rusak, archived masih reachable. | Inventory restricted, config diff, HTTP/purge evidence. |
| P7 | Smoke/browser acceptance pada target yang disetujui. | Tidak ada defect material; loading/download/MFA aman. | Security/data/UI blocker. | Signed acceptance. |
| P8 | Monitor setelah release; verifikasi logs, queue, scheduler, disk, error rate; keluar maintenance. | Stable observation window. | Error/material drift. | Monitoring snapshot dan approval. |

## 18. Keputusan akhir

Release hanya dapat dinyatakan verified setelah seluruh item blocking mempunyai evidence dan tidak ada stop condition aktif. Ketidaktersediaan target adalah **PENDING — ENVIRONMENT NOT AVAILABLE**, bukan PASS. Defect source baru adalah **SOURCE REOPEN REQUIRED**. Penundaan NFR-031/NFR-033 tetap diterima dan tidak boleh diperluas diam-diam ke gate lain.
