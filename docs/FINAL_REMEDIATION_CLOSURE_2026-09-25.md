# Final remediation closure — 25 September 2026

> **Pembaruan D02 — 26 September 2026 (status terkini):** pemulihan MFA oleh operator berwenang kini tersedia sebagai `php artisan admin:recover-mfa <email>` dengan konfirmasi interaktif. Command hanya menerima satu Admin aktif yang cocok, menghapus enrollment TOTP tanpa mengubah password, mencabut sesi database dan versi keamanan lama, serta mencatat satu audit sukses tanpa secret. Jalur command, login ulang, dan enrollment melalui provider MFA normal lulus pada PostgreSQL disposable: **6/6 tes D02, 43 assertion**; regresi auth/audit terfokus **65/65, 380 assertion**. Satu guarded full suite sesudah perubahan lulus **459 passed, 0 failed, 2.924 assertions, 573,83 detik** dan menjadi baseline otomatis terbaru; DoD item 13 tetap **PASS — VERIFIED** pada baseline ini. Runbook [RECOVERY](operations/RECOVERY.md) kini memuat prosedur yang dapat dijalankan. Ketersediaan CLI dan walkthrough di target, build, browser, format DOC/XLS/HEIC, cutover origin D07, operasi target, serta backup/rehearsal D09 tetap gerbang rilis. **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS; RELEASE VERIFICATION PENDING.** Catatan 453/453 dan ketiadaan command di bawah adalah snapshot sebelum D02.

> **Gerbang verifikasi rilis — 26 September 2026:** guardrail test dan build `-ValidateOnly` lulus. Cache route dan config **sementara** pada `storage/testing/release-gate` berhasil dibuat dan dipakai memuat 78 route, termasuk menu aktif, Media, Document, preview, dan ekspor, tanpa `ListMenus`; artefaknya telah dihapus. Cache route normal masih memuat `ListMenus` dan wajib dibangun ulang di target. Build riil belum dijalankan karena wrapper hanya menulis ke `public/build` kerja; browser tidak tersedia; fixture DOC/XLS asli dan HEIC/decoder tidak tersedia. Tidak ada lingkungan origin/worker/TLS/backup target atau rehearsal DB–file–key–config lengkap yang diotorisasi dalam pass ini. Inventory legacy tidak dijalankan karena perintah juga menginventarisasi storage publik kerja; cutover D07 belum dilakukan. `schedule:list` hanya membuktikan dua task hourly terdaftar, bukan eksekusinya. Tidak ada perubahan source atau suite ulang; baseline otomatis tetap **453/453, 2.881 assertion, 192,93 detik**. **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS; RELEASE VERIFICATION PENDING.**

Manifest `public/build` yang sudah ada memuat 15 entri dengan semua file rujukan ditemukan, tanpa sourcemap, `.env`, atau `public/hot`; pemeriksaan artefak lama ini **bukan** bukti build baru. Runbook D02 masih menyatakan belum ada prosedur reset MFA operator yang dapat dijalankan dengan aman; kesiapan pemulihan akun belum lulus. Kebijakan backup D09 sudah disetujui, tetapi pelaksanaan dan pemantauan target tidak tersedia untuk diperiksa.

Pemeriksaan path lokal menemukan `public/storage` sebagai reparse point dan namespace `public/storage/media` ada. Ini mendukung kebutuhan deny D07, tetapi bukan uji HTTP origin: akses URL statis, aturan Nginx, dan cache CDN tetap harus diverifikasi pada target berwenang sebelum rilis.

| DoD Master §16 | Status setelah gerbang ini | Batas bukti |
|---|---|---|
| 14 — browser/visual | PENDING | Browser tidak tersedia; viewport, keyboard, loader, map, preview/MFA belum dijalankan. |
| 15 — production-like | PARTIAL | Cache testing berhasil; build riil, origin, worker, scheduler execution, storage target, TLS/proxy/Turnstile belum diuji. |
| 16 — recovery DB–media–key–config | PARTIAL | Rehearsal P8 hanya subset fixture DB+file; pemulihan identitas/kunci dan seluruh file teracu belum dibuktikan. |
| 17 — dokumentasi | PARTIAL | Runbook aktif mencatat prasyarat dan batas bukti; operasi target dan Guidebook/custody final belum divalidasi. |
| 19 — klaim sesuai bukti | PASS | Cache testing, daftar scheduler, dan inspeksi source tidak disebut sebagai bukti target. |
| 20 — status pending sampai bukti manual | PASS | Rilis tetap VERIFICATION PENDING. |

> **Baseline otomatis authoritative — 26 September 2026:** setelah seluruh koreksi test paket keputusan pemilik dan konfigurasi biaya Argon2id khusus PHPUnit, guarded full suite lulus **453 passed, 0 failed, 2.881 assertions, 192,93 detik**. Ini menggantikan hasil 443/8 dan baseline 441/441 sebelumnya sebagai bukti otomatis terbaru. F22, F24, F30, F34, FR-021, NFR-015, NFR-029, F21, dan D15 tidak menunjukkan regresi dalam batas aplikasi teruji. Source status: **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS**; release tetap **VERIFICATION PENDING** karena gate eksternal yang dicatat di bawah.

> **Refresh Definition of Done:** item 13 (fidelity test, stale expectation, dan regresi otomatis) kini **PASS — VERIFIED** pada baseline 453/453. Item yang bergantung browser, origin, build target, worker/scheduler, TLS, backup, dan recovery tetap tidak dinaikkan oleh suite lokal.

> **Adendum verifikasi paket keputusan 26 September 2026:** satu guarded full suite menghasilkan **443 lulus, 8 gagal, 2.792 assertion, 733,32 detik**. Kegagalan berasal dari enam file test yang masih mengharapkan `published_at` tersembunyi/ditimpa, password lama tanpa kelas karakter baru, atau kunci limiter vendor lama. Setelah asersi diselaraskan dengan keputusan pemilik, keenam file lulus pada rerun terfokus (**53/53 tes**); tes form jadwal tambahan juga lulus. Sesuai batas satu full suite paket, **belum ada full rerun setelah koreksi test**. Baseline repository-wide hijau terakhir tetap 441/441 sebelum paket ini, sehingga status rilis **VERIFICATION PENDING**. Uraian P9/pasca-P9 di bawah bersifat historis; lihat bagian adendum terbaru pada akhir dokumen.

> **Adendum keputusan pemilik 26 September 2026:** [kontrak yang disetujui](OWNER_APPROVED_DECISIONS_2026-09-26.md) menggantikan label keputusan pending dalam snapshot P9/pasca-P9 di bawah. Paket source FR-021, NFR-015/NFR-029, F21, dan D15 telah diterapkan; NFR-031/NFR-033 diterima sebagai penundaan eksplisit. Status tes final paket akan dicatat pada adendum akhir setelah satu guarded full suite. Tidak ada klaim kesiapan rilis atau cutover origin dari implementasi source ini.

> **Pembaruan pasca-P9, 26 September 2026:** empat temuan yang dibuka kembali (F22, F24, F30, F34) telah mendapat koreksi source dan bukti terfokus di lingkungan disposable. Tabel dan analisis P9 di bawah adalah snapshot historis sebelum koreksi, bukan status terbaru. Full suite final pascakoreksi lulus **441 passed, 0 failed, 2.782 assertions, 81,83 detik**. **Status kini: IMPLEMENTATION INCOMPLETE; RELEASE VERIFICATION PENDING.** Lihat adendum terkini di akhir dokumen.

**Snapshot P9 tanggal 25 September: CLOSURE AUDIT COMPLETE — MATERIAL DEFECTS REMAIN.**

**Status pada snapshot P9 (sebelum koreksi): IMPLEMENTATION INCOMPLETE; RELEASE BLOCKED BY MATERIAL DEFECT.** Full suite P9 yang hijau saat itu tidak menutup atomisitas Page yang belum aktif, gap cakupan test, audit events, dan consumer requirement. P9 hanya mengubah laporan/dokumentasi; tidak memperbaiki application source atau mengklaim semua gate selesai. Status pasca-P9 berada pada adendum di akhir dokumen.

Baseline historis: [audit 17 September](../../FULL_REPOSITORY_AUDIT_2026-09-17.md). Execution plan dan DoD: [Master §16](../../MASTER_REMEDIATION_PLAN_2026-09-17.md). Requirement reconciliation: [85-ID matrix](REQUIREMENT_TRACEABILITY.md). Snapshot aktif: [PROJECT_STATE](PROJECT_STATE.md).

## 1. Git dan provenance baseline

- Repository: C:/Users/givar/KULIAH/WEB_KKN/village-cms; branch **main**.
- HEAD: **52022ec6b1e6c24fd1662300ee41a97b24e639e4**.
- Sebelum P9: **228 status entries** dengan -uall; **60 staged**, **146 unstaged**, **51 untracked**. Staged/unstaged saling overlap; bukan penjumlahan jumlah file.
- Kategori status entries: 136 application/schema/runtime, 56 tests, 31 docs, 5 guardrail/config/other.
- Index diff SHA256 awal: fa267485e228924c080aa010c26293dacaa0403c95dafb2e0ecd57261d7f2bf7. P9 tidak mengubah staging. Isi index awal mencakup guardrails dan P1/P2 source/tests serta Product Backlog; status staged sendiri bukan approval/release proof.
- Git diff tidak membawa metadata fase untuk perubahan yang belum di-commit. Karena itu atribusi pre-remediation vs P0–P8 berdasarkan dokumen/laporan/source context, bukan hanya status staged. Tidak ada klaim kronologi per edit yang tidak dapat dibuktikan.
- Sebanyak **457 file hash** source/test/config/route/migration/script serta audit/plan/lockfiles/cache dicatat pada inspeksi awal. Manifest rinci tidak dipersist sebagai artefak, sehingga laporan ini tidak mengklaim perbandingan hash akhir seluruh 457 file dapat direproduksi. P9 tidak melakukan edit application/test/schema/dependency/runtime cache; index diff akhir tetap memiliki SHA256 yang sama dengan baseline.
- Status akhir: **230 entries**, **60 staged**, **146 unstaged**, **53 untracked**; kenaikan dua file berasal dari laporan closure dan matriks requirement baru. File dokumentasi lain telah modified/untracked pada baseline; tidak di-stage ulang.
- Ignored local test env/guardrail/cluster/log/build/cache tidak menjadi bagian paket closure. Guidebook08 hanya metadata/custody; nilai rahasia tidak dibaca/disalin.

## 2. Bukti eksekusi P9

| Evidence ID | Hasil dan batas bukti |
|---|---|
| E-FULL | Full suite P9 melalui safe-test.ps1; 434 passed, 0 failed, 2.688 assertions, 91,45s; skipped/incomplete tidak dilaporkan. 70 *Test.php. Log ignored storage/testing/p9-full-suite.log SHA256 50A2A76F6A01E752A02689159A8F5815E862B75C2AE55886110D974E2469231C. |
| E01 | tests/Feature/P1ASecurityBoundaryTest.php, P1AMediaBoundaryTest.php, SecurityTest.php, SecurityExtendedTest.php, ForcePasswordChangeLivewireTest.php, B14MfaExperienceTest.php; tests/Unit/ContentSecurityTest.php, TurnstileVerifierTest.php. Actual profile snapshot/Livewire/HTTP/upload boundary dan negative cases; bukan real Turnstile service. |
| E02 | tests/Feature/P2DataInvariantTest.php: resource construction, category delta, settings, publication/search, scoped position. PageBuilderServiceTest.php: service child rollback; jangan menaikkan klaim ke parent Filament (E-F22). |
| E02-CONC | tests/Feature/P2SlugConcurrencyTest.php dan P2SingleAdminConcurrencyTest.php: genuine PostgreSQL competing connections, nested retry/exceptions, provisioning lock/anomaly. Bukan DB-global singleton. |
| E03A | tests/Feature/P3AMediaLifecycleVerificationTest.php: bytes/MIME/checksum, final cache headers, D06, archive/restore/delete, failure/stale/orphan, one PUBLIC constraint. P3AMediaReferenceConcurrencyTest.php: actual concurrent Page FK insert/deletion lock, advisory serialization dan rejection archived settings/builder; bukan semua writer end-to-end. |
| E03B | tests/Feature/P3BWatermarkIntegrityTest.php, WatermarkVerificationTest.php, VisibleWatermarkTest.php, WatermarkKeyRotationTest.php: real disposable PNG/other supported fixture bytes, signature/media ID/checksum/format/logo/manual outcome/reprocess. Tidak membuktikan version upgrade/key-history recovery. |
| E03C | tests/Feature/P3CManagedDocumentTest.php dan DocumentTest.php: valid PDF/OOXML bytes, active Filament upload/edit, controlled download/publication/identity. Tidak ada genuine DOC/XLS fixture di tests. |
| E04 | tests/Feature/P4ANavigationTest.php, P4BPreviewIntegrationTest.php, PreviewTokenStoreTest.php, MenuPreviewTest.php dan reconstructed entity Preview tests: HTTP/Livewire, state/destination/identity, ownership/session, no business mutation, deterministic quota. Tanpa browser. |
| E05 | tests/Feature/P5ExportLifecycleTest.php, AdminTableExportTest.php: queued job chain/chunk completion dijalankan terisolasi, CSV/XLSX/PDF parser/hash/ownership/expiry/cleanup/failure. Bukan daemon production worker. |
| E07 | tests/Feature/P7PublicQueryRegressionTest.php: current derivative SELECT bound pada / <=2, /berita <=1, /galeri <=1, /galeri/p7-album-0 <=1 dengan lima media fixture. P7CountSemanticsTest.php, NewsTest.php, DocumentTest.php, PublicSearchTest.php: count/filter semantics. Tidak mengukur ulang angka BEFORE historis dalam P9. |
| E-GUARD | safe-test.ps1 -ValidateOnly exit0; runtime APP_ENV testing, pgsql village_cms_test, 127.0.0.1:5434; local/public/private/admin_exports Storage::fake berada di storage/framework/testing tanpa reparse escape. queue sync, session/cache array; P6DatabaseBackendsTest secara eksplisit memeriksa backend PostgreSQL disposable. |
| E-ROUTES | APP_ROUTES_CACHE=storage/testing/p9-route-review.php dan APP_CONFIG_CACHE testing terpisah; php artisan route:cache --env=testing exit0; bootstrap fresh load 78 routes; current menu, media, original, document, preview, export names hadir, tanpa ListMenus. File sementara dihapus. Normal bootstrap/cache/routes-v7.php tidak diubah dan masih ListMenus. |
| E-SCHEMA | Read-only catalog pada database disposable setelah suite: ketiga migrasi P3/P5 ada; 7 trigger reference; 8 slug unique constraints; media_derivatives_media_id_derivative_type_unique; export_chunks_export_id_page_unique. No migration on working DB. |
| E-F22 | Read-only bootstrap source application with test env (DB server stopped): CreatePage::hasDatabaseTransactions()=false, EditPage::hasDatabaseTransactions()=false. Vendor CreateRecord/EditRecord saves parent before hook; CanUseDatabaseTransactions no-op when false. Child service has own DB transaction. P2DataInvariantTest wraps parent manually, not active editor save. |
| E-GAPS | Targeted current-source references: social_twitter/social_youtube persisted in WebsiteSettings but public footer only Facebook/Instagram; is_featured public consumer only News; Page/Menu/Media lack Auditable and no auth Login/Logout/Failed listener found in app; media callers dispatchSync; ProcessMediaJob no resize/thumbnail generation; verifier literal version1.0. Static evidence, not newly executed failing UI test. |
| E-STATIC | Reference/route/model/migration inspections; rg old class/ListMenus/injector/placeholders/synthetic routes/global middleware disabling; path/secret checks on closure docs; Git status comparison, exact index diff SHA256 and suite-log SHA256 rechecked. No claim whole Git history secret audit or reproducible full source-hash comparison. |
| E-DOC | Current source compared with P8 canonical docs; historical docs clearly classified; Guidebook08 content not read. Narrow P9 corrections for sync media and absent social consumers, plus final state. |
| E-BROWSER | Browser skill read; setupBrowserRuntime succeeded but getDefault returned No browser is available; troubleshooting then agent.browsers.list() returned []. No framework installed, no browser evidence. |
| E-BUILD | Node v24.15.0, npm11.12.1; safe-build.ps1 -ValidateOnly exit0. Wrapper always npm run build -> current public/build with no isolated outDir parameter, so real build not run to avoid replacing working artifacts. public/hot absent; manifest exists but existence is not compile proof. |
| E-RECOVERY | Prior P8 evidence in docs/operations/RECOVERY.md: dump/restore village_cms_test -> village_cms_p8_restore_test and distinct test roots; fixture Admin/Page/Setting/Media/Document, file SHA256 and DocumentDeliveryService resolve. Two other source Media rows had no corresponding copied files. P9 did not repeat restore/key custody. |

Perintah utama dari root repository:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot . -ValidateOnly
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-build.ps1 -ValidateOnly
```

Keduanya exit0. Proses full-suite memakai environment process-scoped berikut agar bootstrap wrapper juga tidak membaca route/config cache kerja:

```powershell
$env:APP_ENV='testing'
$env:APP_ROUTES_CACHE='storage/testing/p9-routes.php'
$env:APP_CONFIG_CACHE='storage/testing/p9-config.php'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot .
```

Hasil authoritative: **434 passed / 0 failed / 2,688 assertions / 91.45 seconds**; skipped/incomplete tidak dilaporkan. Tidak ada full rerun kedua, browser/build/production worker. Status green mengacu apa yang benar-benar diuji, bukan semua requirement.

Disposable cluster memakai C:/laragon/bin/postgresql/pgsql/bin/pg_ctl.exe, data directory storage/testing/p3a-postgresql, -p 5434 -h 127.0.0.1. Start pertama gagal restricted-token sandbox; start yang diotorisasi di luar sandbox berhasil. Setelah suite/schema review, **pg_ctl -m fast -w stop berhasil**. Recovery-named cluster tidak dimulai. Test base mem-fake local/public/private/admin_exports di storage/framework/testing; tidak menggunakan media kerja. Queue default tes sync, session/cache array; test backend khusus PostgreSQL tetap pada DB disposable.

## 3. Ringkasan closure 46 finding

Satu status utama per finding untuk penghitungan; dimensi manual/operasional dan gate tidak hilang ketika status utama VERIFIED FIXED. Verified merujuk batas sempit finding yang memiliki bukti, bukan release readiness.

| Status utama | Jumlah |
|---|---:|
| VERIFIED FIXED | 18 |
| OPERATIONAL GATE PENDING | 2 |
| OWNER DECISION PENDING | 1 |
| PARTIALLY VERIFIED | 15 |
| REOPEN REQUIRED | 4 |
| DOCUMENT-ONLY RESOLUTION | 3 |
| BROWSER GATE PENDING | 2 |
| DEFERRED — ACCEPTANCE REQUIRED | 1 |

**Empat REOPEN REQUIRED:** F22 (P2 Page save transaction), F24 (P7 consumer settings), F30 (P1–P5 audit event coverage), F34 (P6 actual-editor rollback regression). Gap requirement lain tetap ada pada matrix, terutama featured Page/Gallery, optimization, dan version compatibility; tidak ditutup dengan penghapusan kontrol.

## 4. Review semua historical HIGH

| Finding | Severity historis | Status saat ini | Bukti actual dan sisa gate |
|---|---|---|---|
| F01 | HIGH | VERIFIED FIXED | E02; Terbatas pada class/runtime resource; visual admin tetap G-BROWSER. |
| F02 | HIGH | OPERATIONAL GATE PENDING | E-ROUTES; Cache normal bootstrap/cache/routes-v7.php masih memuat ListMenus; regenerasi di target dan uji route wajib. |
| F04 | HIGH | VERIFIED FIXED | E01; E04; Bukti negatif sanitizer dan HTTP tersedia; CSP global tetap report-only, bukan enforcement produksi. |
| F05 | HIGH | VERIFIED FIXED | E01; Snapshot profil teruji; tidak mengklaim semua channel log produksi diaudit. |
| F06 | HIGH | VERIFIED FIXED | E01; Request aplikasi terisolasi teruji; cookie/proxy/session produksi G-OPS. |
| F13 | HIGH | PARTIALLY VERIFIED | E03A; E03B; Gagal reprocess/activation rollback diuji; semua crash storage/HEIC/konkurensi produksi belum. |
| F35 | HIGH | DOCUMENT-ONLY RESOLUTION | E-DOC; Instruksi berbahaya aktif dicabut; pelaksanaan deploy/rollback target belum diuji. |

Severity historis tidak diturunkan. F22 yang historis MEDIUM tetap material data-integrity defect; severity label tidak membolehkan release bila invariant masih gagal.

## 5. Matriks detail F01–F46

Kolom manual/operasional di setiap finding sengaja eksplisit. Tidak ada manual/browser atau production test P9; isolated evidence tidak dipromosikan menjadi operational verification.

### F01 — HIGH — VERIFIED FIXED

- **Issue asli / fase:** Class action Filament tidak tersedia / P2.
- **Current source:** `app/Filament/Resources/GalleryAlbums/Tables/GalleryAlbumsTable.php`; `app/Filament/Resources/Documents/Tables/DocumentsTable.php`; `app/Filament/Resources/Locations/Tables/LocationsTable.php`.
- **Remediasi/current behavior:** Action memakai API Filament 4; tiga list aktif dibangun dalam tes.
- **Owner dependency:** —. **Automated/static evidence:** E02.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Terbatas pada class/runtime resource; visual admin tetap G-BROWSER.

### F02 — HIGH — OPERATIONAL GATE PENDING

- **Issue asli / fase:** Cache route menunjuk ListMenus yang tidak ada / P2/P8.
- **Current source:** `app/Filament/Resources/Menus/MenuResource.php`; `routes/web.php`.
- **Remediasi/current behavior:** Source index EditMenu; cache sementara P9 berisi 78 route valid tanpa ListMenus.
- **Owner dependency:** —. **Automated/static evidence:** E-ROUTES.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/DEPLOYMENT_GUIDE.md`.
- **Residual risk / next gate:** Cache normal bootstrap/cache/routes-v7.php masih memuat ListMenus; regenerasi di target dan uji route wajib.

### F03 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** public/hot memilih localhost Vite / P2/P8.
- **Current source:** `.gitignore`; `vite.config.js`.
- **Remediasi/current behavior:** Hot marker tidak ada dan diabaikan; kontrak paket melarang hot/cache workstation.
- **Owner dependency:** —. **Automated/static evidence:** E-STATIC.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/DEPLOYMENT_GUIDE.md`.
- **Residual risk / next gate:** Bukti higiene lokal; kompilasi aset P9 tidak dijalankan (G-BUILD).

### F04 — HIGH — VERIFIED FIXED

- **Issue asli / fase:** Stored XSS dan HTML/URL tidak konsisten / P1A.
- **Current source:** `app/Support/ContentSecurity.php`; `resources/views/public/news/show.blade.php`; `resources/views/pages/components/rich_text.blade.php`.
- **Remediasi/current behavior:** Sanitasi HTML dan URL dibagi dengan rendering/preview; data lama tidak ditulis ulang.
- **Owner dependency:** —. **Automated/static evidence:** E01; E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md`.
- **Residual risk / next gate:** Bukti negatif sanitizer dan HTTP tersedia; CSP global tetap report-only, bukan enforcement produksi.

### F05 — HIGH — VERIFIED FIXED

- **Issue asli / fase:** Secret TOTP ikut state profil / P1A.
- **Current source:** `app/Filament/Pages/Auth/EditProfile.php`.
- **Remediasi/current behavior:** Allowlist form biasa; provider enrollment tetap dapat memakai secret.
- **Owner dependency:** D02 terpisah. **Automated/static evidence:** E01.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/01_PENGANTAR_DAN_AKSES_ADMIN.md`.
- **Residual risk / next gate:** Snapshot profil teruji; tidak mengklaim semua channel log produksi diaudit.

### F06 — HIGH — VERIFIED FIXED

- **Issue asli / fase:** Session controls tidak persisten pada Livewire / P1A.
- **Current source:** `app/Providers/Filament/AdminPanelProvider.php`; `app/Http/Middleware/ForcePasswordChange.php`.
- **Remediasi/current behavior:** Persistent middleware memeriksa revoked password session, forced password, timeout, dan MFA pada update nyata.
- **Owner dependency:** D15 kebijakan akhir. **Automated/static evidence:** E01.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Request aplikasi terisolasi teruji; cookie/proxy/session produksi G-OPS.

### F07 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Boundary preview/export tidak setara / P1A/P3/P5.
- **Current source:** `bootstrap/app.php`; `routes/web.php`; `app/Http/Controllers/Admin/AdminPdfExportDownloadController.php`.
- **Remediasi/current behavior:** admin.security dan ownership melindungi preview, original, dan export termasuk legacy route.
- **Owner dependency:** D06 approved. **Automated/static evidence:** E01; E03A; E05.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/EXPORTS.md`.
- **Residual risk / next gate:** Route terisolasi teruji; domain/TLS produksi belum.

### F08 — MEDIUM — OWNER DECISION PENDING

- **Issue asli / fase:** Reauth/password/progressive limiter belum memenuhi target / P1B.
- **Current source:** `app/Filament/Pages/Auth/EditProfile.php`; `app/Filament/Pages/Auth/Login.php`.
- **Remediasi/current behavior:** Kontrol yang ada dipertahankan; tidak membuat parameter owner baru.
- **Owner dependency:** D15; D02. **Automated/static evidence:** E01.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Ratifikasi policy password, perubahan email sensitif, reauth dan progressive limiter, kemudian implementasi/tes P1B.

### F09 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Turnstile missing secret fail-open / P1A.
- **Current source:** `app/Services/TurnstileVerifier.php`.
- **Remediasi/current behavior:** Konfigurasi kosong/malformed menjadi 503; transport/invalid response tidak sukses.
- **Owner dependency:** —. **Automated/static evidence:** E01.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/RUNTIME_PREREQUISITES.md`.
- **Residual risk / next gate:** Adapter HTTP fake teruji; hostname/service nyata produksi G-OPS.

### F10 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Security headers tidak konsisten / P1A/P8.
- **Current source:** `app/Http/Middleware/SecurityHeaders.php`; `bootstrap/app.php`; `app/Http/Controllers/Public/MediaDerivativeController.php`.
- **Remediasi/current behavior:** Header pada normal/error/file; file response setPrivate setelah konstruksi; CSP sandbox dipertahankan.
- **Owner dependency:** —. **Automated/static evidence:** E01; E03A.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/DEPLOYMENT_GUIDE.md`.
- **Residual risk / next gate:** HSTS conditional/report-only CSP sesuai source; origin TLS/trusted proxy/secure cookie nyata belum diuji.

### F11 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Validator diuji berbeda dari uploader aktif / P1A/P3.
- **Current source:** `app/Services/MediaInputPolicy.php`; `app/Rules/SafeMediaUpload.php`; `app/Filament/Resources/Media/Schemas/MediaForm.php`.
- **Remediasi/current behavior:** Policy dipakai create/edit/crop dan processing; MIME/dimensi/byte validation.
- **Owner dependency:** D03/D04. **Automated/static evidence:** E01; E03B; E03C.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md`.
- **Residual risk / next gate:** Uploader aktif diuji PNG/PDF dan OOXML; corpus HEIC serta DOC/XLS asli belum tersedia.

### F12 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Unsupported media ditulis publik / P1A/P3.
- **Current source:** `app/Jobs/ProcessMediaJob.php`; `app/Services/MediaDeliveryService.php`.
- **Remediasi/current behavior:** Kandidat privat dan approval gate; tidak ada fallback original publik baru.
- **Owner dependency:** D06/D07. **Automated/static evidence:** E01; E03A; E03B.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/MEDIA_LEGACY_CUTOVER.md`.
- **Residual risk / next gate:** Jalur baru teruji; bytes legacy masih dapat dilayani statis sampai G-ORIGIN.

### F13 — HIGH — PARTIALLY VERIFIED

- **Issue asli / fase:** Reprocess menghapus derivative lama sebelum aman / P3-A.
- **Current source:** `app/Jobs/ProcessMediaJob.php`; `app/Services/MediaDeletionService.php`.
- **Remediasi/current behavior:** Attempt token, staging privat, generasi unik dan aktivasi setelah verifikasi; file lama dipertahankan.
- **Owner dependency:** D07/D08. **Automated/static evidence:** E03A; E03B.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/MEDIA_LEGACY_CUTOVER.md`.
- **Residual risk / next gate:** Gagal reprocess/activation rollback diuji; semua crash storage/HEIC/konkurensi produksi belum.

### F14 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Metadata replacement salah dan original HEIC hilang / P3-A.
- **Current source:** `app/Jobs/ProcessMediaJob.php`; `app/Filament/Resources/Media/Pages/EditMedia.php`.
- **Remediasi/current behavior:** Metadata input divalidasi ulang; konversi ke candidate berbeda; checksum original dijaga.
- **Owner dependency:** D06. **Automated/static evidence:** E03A; E03B.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md`.
- **Residual risk / next gate:** Original PNG teruji immutable; konversi HEIC nyata dan original legacy yang sudah hilang tidak dibuktikan/direkonstruksi.

### F15 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Referensi settings/location terlewat saat delete / P3-A/P3-C.
- **Current source:** `app/Services/MediaReferenceCoordinator.php`; `app/Services/SettingsAndLocationMediaUsageResolver.php`; `database/migrations/2026_09_23_000002_add_media_cleanup_and_reference_guards.php`.
- **Remediasi/current behavior:** Usage diperluas; tujuh trigger FK serta advisory/row lock mengoordinasikan attach-delete.
- **Owner dependency:** D13. **Automated/static evidence:** E03A; E03C; E-SCHEMA.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/MEDIA_LEGACY_CUTOVER.md`.
- **Residual risk / next gate:** Race Page FK dua koneksi dan advisory serialization teruji; setiap writer News/Gallery/Document/Location/settings/builder belum dirace end-to-end.

### F16 — MEDIUM — OPERATIONAL GATE PENDING

- **Issue asli / fase:** Archive/delete tidak mencabut URL statis / P3-A.
- **Current source:** `app/Services/MediaDeletionService.php`; `app/Services/MediaDeliveryService.php`.
- **Remediasi/current behavior:** Controlled route revoke, restore eligible, cleanup failure retryable; generasi baru privat.
- **Owner dependency:** D07 approved; sisa D08. **Automated/static evidence:** E03A.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/MEDIA_LEGACY_CUTOVER.md`.
- **Residual risk / next gate:** Deny origin legacy /storage/media/** dan /storage/originals/** belum diterapkan/diverifikasi. Tidak bisa menghapus cache klien yang sudah diunduh.

### F17 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Verified hanya metadata, bukan bytes / P3-B.
- **Current source:** `app/Services/WatermarkVerificationService.php`; `app/Jobs/ProcessMediaJob.php`; `app/Filament/Resources/Media/Tables/MediaTable.php`.
- **Remediasi/current behavior:** HMAC expected Media/installation/type/version + checksum akhir + decoder; manual result/status/notification selaras.
- **Owner dependency:** D05 approved. **Automated/static evidence:** E03B.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md`.
- **Residual risk / next gate:** Gambar fixture/tamper teruji; missing checksum legacy unverifiable, key history/reconciliation dan race manual verify vs reprocess belum dibuktikan.

### F18 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Logo Media ID ditafsirkan sebagai path / P3-B.
- **Current source:** `app/Services/WatermarkService.php`; `app/Filament/Pages/WebsiteSettings.php`.
- **Remediasi/current behavior:** ID diselesaikan sebagai managed Media eligible dengan file lokal aman; logo invalid gagal eksplisit.
- **Owner dependency:** D14 approved. **Automated/static evidence:** E03B.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/03_KELOLA_WEBSITE_DAN_TAMPILAN.md`.
- **Residual risk / next gate:** Logo fixture benar dan invalid teruji; logo produksi tidak diproses.

### F19 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Format/selector/download/builder Document salah / P3-C.
- **Current source:** `app/Services/DocumentFilePolicy.php`; `app/Services/DocumentDeliveryService.php`; `app/Services/DocumentFileAssignment.php`; `resources/views/pages/components/documents.blade.php`.
- **Remediasi/current behavior:** PDF/Word/Excel privat, MIME sebenarnya, checksum dan managed Document identity; legacy mapping berdasar relasi nyata.
- **Owner dependency:** D03/D04/D13 approved. **Automated/static evidence:** E03C.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/02_KELOLA_KONTEN.md`.
- **Residual risk / next gate:** PDF/DOCX/XLSX teruji; genuine DOC/XLS serta rekonsiliasi legacy belum. Macro/encrypted variant bukan dukungan yang diklaim.

### F20 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Category snapshot menghapus kategori dari sesi lain / P2.
- **Current source:** `app/Services/CategoryMutationService.php`.
- **Remediasi/current behavior:** Delta create/update/explicit delete, policy/relationship guard, transaksi dan lock.
- **Owner dependency:** —. **Automated/static evidence:** E02.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/02_KELOLA_KONTEN.md`.
- **Residual risk / next gate:** Stale snapshot dan rollback guarded deletion teruji pada mutation service; bukan bukti semua multi-tab browser.

### F21 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Predicate publication berbeda / P2.
- **Current source:** `app/Models/Page.php`; `app/Traits/HasContentLifecycle.php`; `app/Http/Controllers/PageController.php`.
- **Remediasi/current behavior:** Shared published + waktu tercapai; detail/scope/search memakai state persisted.
- **Owner dependency:** D11 approved; D01. **Automated/static evidence:** E02; E03C; E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Past/future/null/draft fixture teruji; saving status published dapat menimpa published_at dengan now pada Page/trait. Scheduling roundtrip harus diperiksa P2, bukan dinormalisasi massal.

### F22 — MEDIUM — REOPEN REQUIRED

- **Issue asli / fase:** Parent Page tersimpan terpisah dari children / P2 (integrasi P4).
- **Current source:** `app/Filament/Resources/Pages/Pages/CreatePage.php`; `app/Filament/Resources/Pages/Pages/EditPage.php`; `app/Services/PageBuilderService.php`.
- **Remediasi/current behavior:** Service menjaga ID/layout/settings dengan transaksi child, tetapi transaksi parent+hook tidak aktif pada source saat ini.
- **Owner dependency:** —. **Automated/static evidence:** E02; E-F22.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** P2: CreatePage/EditPage hasDatabaseTransactions=false pada runtime. Parent ditulis sebelum afterCreate/afterSave; failure child tidak tercakup rollback parent. Tes P2 memberi outer transaction sendiri.

### F23 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** GET menulis Menu; sumber header/footer berbeda / P4-A.
- **Current source:** `app/Services/NavigationResolver.php`; `app/Filament/Resources/Menus/Pages/EditMenu.php`; `app/Filament/Resources/Menus/Pages/CreateMenu.php`; `app/Providers/AppServiceProvider.php`.
- **Remediasi/current behavior:** Read-only resolver, explicit authorized create, header/footer location berbeda; ID/hierarchy/order dijaga.
- **Owner dependency:** D11/D13/D14. **Automated/static evidence:** E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/03_KELOLA_WEBSITE_DAN_TAMPILAN.md`.
- **Residual risk / next gate:** HTTP/Livewire/data behavior teruji; tampilan browser tetap G-BROWSER.

### F24 — MEDIUM — REOPEN REQUIRED

- **Issue asli / fase:** Settings load/save/default/consumer tidak utuh / P2/P3/P4/P7.
- **Current source:** `app/Services/SettingsService.php`; `app/Filament/Pages/WebsiteSettings.php`; `resources/views/partials/footer.blade.php`.
- **Remediasi/current behavior:** Batch atomic/default/cache dan potensi_3_custom_url diperbaiki; media limit/timeout/logo terhubung.
- **Owner dependency:** D14 approved; D10 terpisah. **Automated/static evidence:** E02; E03B; E04; E-GAPS.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** P7: social_twitter dan social_youtube masih field tersimpan tanpa consumer publik. notification_email menunggu D10. Jangan menutup keseluruhan F24 hanya dari core roundtrip.

### F25 — MEDIUM — DOCUMENT-ONLY RESOLUTION

- **Issue asli / fase:** Dokumentasi mengklaim email kontak / P8 / D10.
- **Current source:** `app/Http/Controllers/Public/ContactController.php`; `docs/PROJECT_STATE.md`.
- **Remediasi/current behavior:** Dokumen aktif menyatakan inbox tanpa email dan memisahkan keputusan owner.
- **Owner dependency:** D10. **Automated/static evidence:** E-DOC; tests/Feature/ContactMessageTest.php.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/06_PEMECAHAN_MASALAH_FAQ.md`.
- **Residual risk / next gate:** Mismatch dokumen dikoreksi; inbox-only bukan ratifikasi product policy. D10 tetap terbuka.

### F26 — MEDIUM — BROWSER GATE PENDING

- **Issue asli / fase:** Preview kehilangan state/destination/assets / P4-B.
- **Current source:** `app/Filament/Support/PreviewStateNormalizer.php`; `app/Services/Preview/PreviewDraftStore.php`; `app/Services/Preview/PreviewTemporaryAssets.php`.
- **Remediasi/current behavior:** Normalizer/renderer/temp assets/draft state memakai kontrak bersama; tidak menyimpan business rows.
- **Owner dependency:** D01. **Automated/static evidence:** E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/PREVIEW.md`.
- **Residual risk / next gate:** HTTP/Livewire state parity teruji; renderer/layout/interaksi iframe dan mobile belum diperiksa browser.

### F27 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Quota/prune/corrupt token tidak andal / P4-B.
- **Current source:** `app/Services/Preview/PreviewTokenStore.php`; `routes/console.php`.
- **Remediasi/current behavior:** Owner row lock, deterministic eviction, corrupt payload fail-closed, prune hourly.
- **Owner dependency:** —. **Automated/static evidence:** E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/PREVIEW.md`.
- **Residual risk / next gate:** Quota/TTL/corrupt payload teruji; simultaneous creates dan scheduler target belum teruji.

### F28 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Export completed sebelum artefak valid / P5.
- **Current source:** `app/Services/ExportArtifactService.php`; `app/Jobs/Exports`; `app/Services/AdminTablePdfExportService.php`.
- **Remediasi/current behavior:** Chunk receipt, ukuran/checksum/parser final sebelum completion; failure tidak memberi download sukses.
- **Owner dependency:** D12 current non-snapshot. **Automated/static evidence:** E05.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/EXPORTS.md`.
- **Residual risk / next gate:** Lifecycle fixture CSV/XLSX/PDF teruji; worker/storage produksi dan value snapshot tidak dibuktikan.

### F29 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Download/cleanup tidak mengikuti state / P5.
- **Current source:** `app/Services/AdminExportCleanupService.php`; `app/Support/Exports/ControlledCsvDownloader.php`; `app/Support/Exports/ControlledXlsxDownloader.php`.
- **Remediasi/current behavior:** Owner/completion/expiry/checksum dan cleanup retryable; file unsafe tidak dihapus.
- **Owner dependency:** D12. **Automated/static evidence:** E05.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/EXPORTS.md`.
- **Residual risk / next gate:** Aplikasi terisolasi lulus; worker/scheduler, disk failures produksi dan overlap download/cleanup target belum lengkap.

### F30 — MEDIUM — REOPEN REQUIRED

- **Issue asli / fase:** Audit event tidak lengkap / sukses sebelum hasil / P1–P5.
- **Current source:** `app/Models/Concerns/Auditable.php`; `app/Services/AuditLogService.php`; `app/Filament/Pages/Auth/EditProfile.php`; `app/Services/ExportAuditService.php`.
- **Remediasi/current behavior:** Password setelah save dan export lifecycle events diperbaiki; News/settings dan redaction ada.
- **Owner dependency:** D08; D15. **Automated/static evidence:** E01; E05; E-GAPS; tests/Feature/AuditTest.php.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** P1–P5: login/logout/failed-login listener serta mutation audit Page/Menu/Media belum ditemukan. Suite hanya membuktikan subset; DB bukan append-only. Audit manual retention D08 juga belum memiliki prosedur approved/teruji.

### F31 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Search array/escaping wildcard salah / P2.
- **Current source:** `app/Http/Controllers/Public/SearchController.php`.
- **Remediasi/current behavior:** Scalar string, UTF-8/length bound dan literal wildcard escaping.
- **Owner dependency:** —. **Automated/static evidence:** E02; tests/Feature/PublicSearchTest.php.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/05_PANDUAN_TAMPILAN_PUBLIK.md`.
- **Residual risk / next gate:** PostgreSQL malformed input/literal wildcard/Unicode/limit teruji.

### F32 — LOW — VERIFIED FIXED

- **Issue asli / fase:** Pagination hilang filter; counts menyesatkan / P2/P7.
- **Current source:** `app/Http/Controllers/Public/NewsController.php`; `app/Http/Controllers/Public/DocumentController.php`; `app/Filament/Widgets/VillageStatsWidget.php`; `resources/views/public/search.blade.php`.
- **Remediasi/current behavior:** withQueryString dan bounded-result wording; widget dokumen menjelaskan status publikasi.
- **Owner dependency:** D11. **Automated/static evidence:** E07; tests/Feature/NewsTest.php; tests/Feature/DocumentTest.php; tests/Feature/PublicSearchTest.php.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/05_PANDUAN_TAMPILAN_PUBLIK.md`.
- **Residual risk / next gate:** Query/response assertions teruji; layout pagination belum browser.

### F33 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Preview tests tidak terdiscovery/rusak / P0/P4/P6.
- **Current source:** `tests/Feature/PagePreviewTest.php`; `tests/Feature/NewsPreviewTest.php`; `tests/Feature/DocumentPreviewTest.php`; `tests/Feature/GalleryPreviewTest.php`; `tests/Feature/MediaPreviewTest.php`; `tests/Feature/CategoryPreviewTest.php`.
- **Remediasi/current behavior:** Encoding/syntax dan stale class direkonstruksi; 70 Test.php menjalankan 434 cases.
- **Owner dependency:** —. **Automated/static evidence:** E-FULL; E-STATIC.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Discovery lengkap untuk suite saat ini; bukan bukti semua requirement teruji.

### F34 — MEDIUM — REOPEN REQUIRED

- **Issue asli / fase:** Placeholder/synthetic test memberi false confidence / P6.
- **Current source:** `tests/Feature/P2DataInvariantTest.php`; `tests/Feature/PageBuilderServiceTest.php`; `tests/TestCase.php`; `tests/Feature/P6DatabaseBackendsTest.php`.
- **Remediasi/current behavior:** Placeholder/upload synthetic dibuang; disk fake dan DB-backed integration ditambah.
- **Owner dependency:** —. **Automated/static evidence:** E-FULL; E-F22.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** P6: test parent rollback masih membuat outer DB::transaction sendiri; tidak membuktikan create/edit Filament aktif. Pertahankan green suite sebagai hasil aktual, bukan proof F22.

### F35 — HIGH — DOCUMENT-ONLY RESOLUTION

- **Issue asli / fase:** Deployment rutin berisiko drop data / expose DB / P8.
- **Current source:** `docs/DEPLOYMENT_GUIDE.md`; `README.md`.
- **Remediasi/current behavior:** Fresh/update/rollback/recovery terpisah; key/data dipertahankan; artifact hygiene.
- **Owner dependency:** D09. **Automated/static evidence:** E-DOC.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/RECOVERY.md`.
- **Residual risk / next gate:** Instruksi berbahaya aktif dicabut; pelaksanaan deploy/rollback target belum diuji.

### F36 — MEDIUM — DOCUMENT-ONLY RESOLUTION

- **Issue asli / fase:** Prasyarat runtime tidak lengkap / P8/P9.
- **Current source:** `docs/operations/RUNTIME_PREREQUISITES.md`; `docs/operations/QUEUE_AND_SCHEDULER.md`.
- **Remediasi/current behavior:** Matriks dependency dan operasi; P9 mengoreksi media dispatchSync agar tidak diklaim queued.
- **Owner dependency:** D09/D10. **Automated/static evidence:** E-DOC; E-BUILD.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/DEPLOYMENT_GUIDE.md`.
- **Residual risk / next gate:** Dokumentasi bukan bukti worker/TLS/HEIF target (G-OPS/G-BUILD/G-FORMAT).

### F37 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Example/self-test guardrail tidak cocok validator / P0/P6.
- **Current source:** `.guardrails.example.json`; `scripts/guardrails/self-test.ps1`; `scripts/guardrails/validate-test-database.ps1`.
- **Remediasi/current behavior:** Host/port disertakan; wrapper ValidateOnly dan full suite lolos target terisolasi.
- **Owner dependency:** —. **Automated/static evidence:** E-GUARD.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/DEVELOPER_SAFETY.md`.
- **Residual risk / next gate:** Validator positif nyata lulus; keseluruhan self-test tidak dijalankan P9 karena membuat commit di repo sementara. Source negative cases tersedia, jangan klaim dieksekusi.

### F38 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Recovery User/DB-only dan tanpa restore bukti / P8.
- **Current source:** `docs/operations/RECOVERY.md`; `app/Console/Commands/ProvisionAdmin.php`.
- **Remediasi/current behavior:** Admin yang benar, recovery unit DB/file/key/config, rehearsal subset disposable.
- **Owner dependency:** D02/D09. **Automated/static evidence:** E-RECOVERY.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/07_PANDUAN_TEKNIS_DAN_MAINTENANCE.md`.
- **Residual risk / next gate:** Semua row/file, dekripsi dengan restored keys, history watermark, TLS/worker restore belum dibuktikan.

### F39 — LOW — VERIFIED FIXED

- **Issue asli / fase:** Lookup derivative/view query berulang / P7.
- **Current source:** `app/Models/Media.php`; `app/Http/Controllers/PublicController.php`; `app/Providers/AppServiceProvider.php`.
- **Remediasi/current behavior:** Reuse loaded relation dan eager loading; URL tetap controlled.
- **Owner dependency:** —. **Automated/static evidence:** E07.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Batas query current fixture: home <=2; news/gallery/album <=1. Tidak ada klaim performa luas/benchmark produksi.

### F40 — INFO — DEFERRED — ACCEPTANCE REQUIRED

- **Issue asli / fase:** Potensi kapasitas media/PDF/map/CDN / P7 investigation.
- **Current source:** `app/Jobs/ProcessMediaJob.php`; `app/Services/AdminTablePdfExportService.php`; `app/Services/DocumentFilePolicy.php`; `app/Http/Controllers/Public/MapController.php`; `resources/views/layouts/public.blade.php`.
- **Remediasi/current behavior:** Batas file/row ada; media sinkron/full-size, PDF memory, seluruh lokasi dan CDN tetap perlu ukuran target.
- **Owner dependency:** D14; acceptance kapasitas. **Automated/static evidence:** E-STATIC; E05.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/RUNTIME_PREREQUISITES.md`.
- **Residual risk / next gate:** Tetapkan workload/budget dan ukur target; tidak ada acceptance risiko kapasitas yang dicatat. Jangan mengoptimalkan spekulatif.

### F41 — LOW — BROWSER GATE PENDING

- **Issue asli / fase:** Loader download / parent map keydown / P7.
- **Current source:** `resources/views/layouts/public.blade.php`; `resources/js/maps.js`.
- **Remediasi/current behavior:** Source concern dipertahankan sampai reproduksi browser, tanpa perubahan visual spekulatif.
- **Owner dependency:** —. **Automated/static evidence:** E-BROWSER.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** Runtime browser tidak tersedia; same-tab download/back-forward, descendant Enter/Space/focus, viewport/zoom wajib diperiksa.

### F42 — LOW — VERIFIED FIXED

- **Issue asli / fase:** Global search overwrite category / P4-A.
- **Current source:** `app/Filament/Providers/GlobalSearchProvider.php`.
- **Remediasi/current behavior:** Accumulate existing category, dedup label+URL, visible item dan max20.
- **Owner dependency:** —. **Automated/static evidence:** E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/01_PENGANTAR_DAN_AKSES_ADMIN.md`.
- **Residual risk / next gate:** Active provider regression lulus; bukan pencarian publik yang diubah.

### F43 — LOW — PARTIALLY VERIFIED

- **Issue asli / fase:** Ownership legacy/dead code dan consumer kabur / P7.
- **Current source:** `app/Filament/Resources/Pages/Tables/PagesTable.php`; `app/Filament/Resources/Menus/Pages/CreateMenu.php`; `app/Http/Controllers/MediaController.php`; `database/factories/UserFactory.php`; `app/Http/Controllers/PublicController.php`.
- **Remediasi/current behavior:** Active CreateMenu/PublicController/PDF parser dipertahankan; kandidat lain tidak dihapus tanpa bukti runtime menyeluruh.
- **Owner dependency:** D14. **Automated/static evidence:** E-STATIC; E04; E07.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/PROJECT_STATE.md`.
- **Residual risk / next gate:** PagesTable/MediaController/UserFactory tak terdaftar pada path aktif yang ditelusuri, tetapi disposal belum disahkan; featured Page/Gallery belum punya consumer (R-GAPS).

### F44 — LOW — VERIFIED FIXED

- **Issue asli / fase:** Single admin dan slug race / P2.
- **Current source:** `app/Console/Commands/ProvisionAdmin.php`; `app/Traits/GeneratesUniqueSlug.php`.
- **Remediasi/current behavior:** Provisioning advisory transaction lock; exact23505 slug constraint + bounded savepoint retry.
- **Owner dependency:** —. **Automated/static evidence:** E02-CONC; E-SCHEMA.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/operations/RECOVERY.md`.
- **Residual risk / next gate:** Supported command dan slug race teruji PostgreSQL, termasuk outer transaction; direct SQL tidak dilindungi singleton admin constraint.

### F45 — MEDIUM — VERIFIED FIXED

- **Issue asli / fase:** Regex injector PDF tidak aman / P3-B.
- **Current source:** `app/Services/WatermarkService.php`; `app/Jobs/ProcessMediaJob.php`; `app/Services/DocumentFilePolicy.php`.
- **Remediasi/current behavior:** PDF injector tidak aktif; PDF memakai validation/delivery Document, bukan watermark image verified.
- **Owner dependency:** D03/D04 approved. **Automated/static evidence:** E03B; E03C; E-STATIC.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md`.
- **Residual risk / next gate:** Watermark PDF ditunda berdasarkan keputusan; dukungan dokumen PDF dipertahankan, bukan klaim watermark PDF terverifikasi.

### F46 — MEDIUM — PARTIALLY VERIFIED

- **Issue asli / fase:** Reorder bentrok posisi unik antara update / P2/P4.
- **Current source:** `app/Services/ScopedPositionService.php`; `app/Filament/Resources/Menus/MenuResource.php`; `app/Filament/Resources/GalleryAlbums/Pages/EditGalleryAlbum.php`.
- **Remediasi/current behavior:** Lock/transaksi, reserve temporary range, assign final positions.
- **Owner dependency:** —. **Automated/static evidence:** E02; E04.
- **Manual/browser evidence:** tidak dijalankan; bila UI relevan, G-BROWSER. **Operational evidence:** tidak ada target production/origin evidence; E-ROUTES/E-SCHEMA/E-RECOVERY hanya disposable bila disebut.
- **Documentation evidence:** `docs/guidebook/03_KELOLA_WEBSITE_DAN_TAMPILAN.md`.
- **Residual risk / next gate:** Swap gallery/menu/submenu teruji; concurrent reorder lengkap dan failure pada seluruh path masih pending.


## 6. Material gap detail / bounded reopening

### F22 + F34 — parent Page tidak dalam logical transaction aktif

**Current evidence:** application CreatePage/EditPage tidak mendefinisikan hasDatabaseTransactions=true dan panel tidak mengaktifkan databaseTransactions. Vendor default false. Read-only instantiated page inspection mengembalikan false untuk kedua class. Vendor create/save menyimpan parent lalu memanggil afterCreate/afterSave; child service baru membuka transaksinya sendiri. GeneratesUniqueSlug membungkus satu parent write saja, bukan hook setelahnya.

**Failure implication:** child persistence dapat rollback sementara parent insert/update sudah commit. PageBuilderService ID/layout preservation tidak mengubah boundary ini. Existing P2DataInvariantTest::test_page_builder_child_failure_rolls_back_parent_and_successful_roundtrip_preserves_identity_and_metadata memasok DB::transaction dari test; PageBuilderServiceTest hanya membuktikan child rollback. Jadi 434/434 tidak membuktikan F22 acceptance. Tidak ada failure injection editor baru dalam P9, dan tidak perlu mengarang hasil mutation test: disabled transaction runtime dan urutan source sudah membuktikan boundary hilang.

**Owning phase:** buka P2 integration dan P6 regression secara sempit: transaksi aktif meliputi parent+relationships+hook, lalu regression melalui actual Filament create/edit dengan child failure. Tinjau integrasi P4 tanpa mengubah preview behavior. Karena perubahan sebelumnya belum di-commit, P9 tidak mengklaim tepat fase mana yang menghapus opt-in tersebut.

### F24 / D14 dan requirement consumer

Core settings batch/default/cache/overlay tetap valid. Social Twitter/X dan YouTube tersimpan namun public footer tidak menggunakannya; hanya Facebook/Instagram. Notification email tetap D10. Featured News dikonsumsi public controller; Page/Gallery is_featured belum consumer publik. Optimization/resize/thumbnail belum pipeline aktual (NFR-015/NFR-029); nilai max_image_* dipakai untuk penolakan input, bukan resize. D14 tidak menyetujui penghapusan kebutuhan. Reopen scoped P7 consumer; optimization/version work memerlukan lingkup P3 yang jelas, bukan optimasi spekulatif dalam P9.

### F30 audit coverage

Password success setelah save/rollback dan export outcome teruji. News/settings memakai Auditable. Page/Menu/Media tidak memakai trait itu dan tidak ada listener auth Login/Logout/Failed yang ditemukan pada app providers/login. Revised requirement §10 menuntut event tersebut. Model/policy immutability tidak berarti database append-only. Reopen terbatas ke event boundary yang memang diwajibkan, redaction, setelah commit dan dedup; bukan audit framework baru. Kebijakan retensi manual D08 memerlukan prosedur tersendiri.

### Requirement lainnya

NFR-033: version1.0 dan satu trusted key tidak menyediakan multi-version/backward-verification dispatch. NFR-031: ShouldQueue dengan dispatchSync tidak membuktikan background queue requirement; ratifikasi interpretasi atau scoped implementation. Publication future timestamp ketika status diubah ke published dapat ditimpa now oleh Page/HasContentLifecycle; shared query predicate membaca data persisted dengan benar, tetapi scheduling roundtrip belum boleh dianggap terverifikasi. Jangan melakukan backfill/mass publication.

## 7. Owner decisions D01–D15

Master §4 adalah daftar opsi/rekomendasi awal, bukan log persetujuan terbaru. P9 menyilangkan approved decisions pada instruksi remediasi dengan PROJECT_STATE, RECOVERY, cutover, dan source P3/P4/P5. Dokumen historis yang masih mengatakan OWNER DECISION REQUIRED tidak membatalkan persetujuan eksplisit yang datang kemudian; tidak ditemukan canonical decision record baru yang bertentangan. Tabel ini menyimpan interpretasi serta dependency, tanpa mengambil keputusan baru.

| ID | Status | Kontrak / current implementation | Dampak / gate |
|---|---|---|---|
| D01 | OWNER DECISION PENDING | Draft implemented; choose final draft policy vs historical revision. | Forms/status/preview/docs/tests; policy acceptance needed before final requirement sign-off, no mass-publish. |
| D02 | OWNER DECISION PENDING | MFA without recovery-code workflow; choose authorized operator recovery vs codes. | Admin/auth/recovery runbook; operational recovery policy blocks recovery closure. |
| D03 | APPROVED B | Defer PDF watermark; remove unsafe injector. | P3-B/P3-C source and P8 docs agree; PDF remains Document format. |
| D04 | APPROVED B | PDF + Word + Excel (.pdf/.doc/.docx/.xls/.xlsx). | DocumentFilePolicy and guidebook agree; DOC/XLS genuine fixtures pending. |
| D05 | APPROVED A | Metadata authenticity + server byte checksum + actual format; no transformation-resilience. | Verifier and guidebook agree; future algorithm/key-history capability still requirement gap. |
| D06 | APPROVED A | Authorized Admin controlled original access for editing; Guest denied. | P3-A route/HTTP tests; known legacy static original still origin gate. |
| D07 | APPROVED CUSTOM | Archive revokes new public requests including known direct URLs; restore eligible only. | Controlled route tested; legacy-origin deployment not applied; release operational gate. |
| D08 | PARTIALLY APPROVED | Audit log six-month manual-retention boundary only. | Current UI/model immutable; reviewed manual procedure pending. Media/contact/other retention remains owner decision; no age purge inferred. |
| D09 | OWNER DECISION PENDING | No final RPO/RTO/schedule/retention/off-host/encryption/operator/verification frequency. | P8 runbook and limited rehearsal; backup policy blocks recovery/operational sign-off. |
| D10 | OWNER DECISION PENDING | Current contact stores inbox; no email notification. | Choose inbox-only or email recipient/retry contract; notification_email saved but unused, no source implementation authorized by docs. |
| D11 | APPROVED A | Published AND published_at <= now for public availability. | Shared predicate present/tests pass; scheduling edit transition timestamp overwrite still targeted gap. |
| D12 | OWNER DECISION PENDING | Current export ID/chunk consistency is not value snapshot. | Choose accepted limited consistency vs snapshot; export policy sign-off pending, no implicit acceptance from green tests. |
| D13 | APPROVED B | Builder references managed Document identity, not raw Media ID. | P3-C schema state/renderer and tests agree; legacy mapping/reconciliation pending. |
| D14 | APPROVED A | Legitimate existing controls need real consumers; cannot silently remove requirements. | Logo/limits/core settings consumers present, social/featured/optimization gaps remain (F24 and traceability). |
| D15 | OWNER DECISION PENDING | Current password/rate/reauth remains existing baseline. | Choose final parameters; P1B implementation blocked, current tests do not satisfy unapproved stronger policy. |

Keputusan terbuka memblokir sign-off policy/requirement yang terkait, bukan alasan merombak fungsi yang sudah teruji. Full recovery D02/D09 dan keamanan D15 penting untuk persetujuan operasional; bila owner hendak merilis dengan penundaan, acceptance tertulis harus menyebut risiko, penanggung jawab, dan syarat penyelesaian. P9 tidak memberi acceptance.

## 8. Traceability summary

85 ID unik direkonsiliasi dalam [REQUIREMENT_TRACEABILITY](REQUIREMENT_TRACEABILITY.md); 47 SRS +38 Watermark. 11 arahan Revisi dipetakan melalui locator bagian tanpa membuat requirement ID baru.

| Status | Jumlah |
|---|---:|
| SATISFIED — VERIFIED | 22 |
| SATISFIED — PARTIALLY VERIFIED | 36 |
| OWNER DECISION PENDING | 3 |
| IMPLEMENTED — MANUAL VERIFICATION PENDING | 10 |
| DEFERRED | 1 |
| NOT IMPLEMENTED | 3 |
| SUPERSEDED BY APPROVED DECISION | 10 |

NOT IMPLEMENTED: NFR-015 optimization, NFR-029 verify after optimization yang belum ada, NFR-033 perubahan versi algoritma dengan kompatibilitas. Partial juga dapat berarti subset belum diimplementasikan; lihat gate baris, bukan hanya label.

## 9. Definition of Done — teks Master §16 tetap utuh

| No. | Kondisi asli | Keputusan P9 | Alasan / bukti |
|---|---|---|---|
| 1 | **Seluruh F01–F46 memiliki status penutupan yang jelas:** fixed, disproven dengan bukti, document-only yang disahkan, atau deferred dengan alasan dan risiko yang diterima. | PARTIAL — VERIFICATION PENDING | Semua 46 memiliki status eksplisit, tetapi empat REOPEN dan beberapa gate belum memenuhi penutupan/acceptance final. |
| 2 | **Tidak ada HIGH security/data-loss finding yang belum terselesaikan tetapi tetap diklaim release-ready.** | PASS — IMPLEMENTED / EVIDENCE SUFFICIENT | Tidak ada klaim release-ready; HIGH F02/F13 tetap gate. Defect atomisitas F22 (historis MEDIUM) juga memblokir rilis. |
| 3 | **Seluruh 85 requirement telah direkonsiliasi** terhadap keputusan owner, implementasi, test, dan dokumentasi. Nomor requirement yang tidak ditemukan tidak diisi dengan asumsi. | PASS — IMPLEMENTED / EVIDENCE SUFFICIENT | 85 ID asli direkonsiliasi; gap implementasi dan keputusan tetap dicatat, bukan diluluskan. |
| 4 | Model satu desa, satu admin, Guest publik, dan seluruh modul bisnis tetap dipertahankan. | PASS — IMPLEMENTED / EVIDENCE SUFFICIENT | Source/model/route dan tes mempertahankan one village, single Admin supported boundary, Guest publik; tidak ada RBAC/multitenancy baru. |
| 5 | Perubahan behavior yang diperlukan memiliki alasan, keputusan, dan compatibility plan. | PARTIAL — VERIFICATION PENDING | Compatibility media/document/keys dicatat; D01/D02/sisa D08/D09/D10/D12/D15 serta consumer D14 belum selesai. |
| 6 | Secret TOTP tidak muncul pada state profil atau log; session controls berlaku pada jalur request yang relevan. | PASS — VERIFIED | P1ASecurityBoundaryTest menjalankan snapshot profil dan actual Livewire session/MFA/timeout boundaries. |
| 7 | Save konten/relasi tidak menghasilkan perubahan sebagian pada kegagalan yang diuji. | BLOCKED — MATERIAL DEFECT | E-F22: transaksi Filament create/edit false; child rollback tidak membuktikan parent rollback pada path aktif. |
| 8 | Media reprocess tidak menghilangkan derivative aktif sebelum pengganti aman. | PASS — VERIFIED | P3-A/B real fixture failed reprocess mempertahankan derivative aktif; HEIC/production crash corpus tetap batas bukti terpisah. |
| 9 | Unsupported/unverified file tidak diterbitkan melalui jalur yang melewati pemeriksaan. | PARTIAL — VERIFICATION PENDING | New input/candidate gate teruji, legacy static URL serta corpus DOC/XLS/HEIC belum tertutup. |
| 10 | Makna watermark verification sesuai bukti yang benar-benar diperiksa. | PASS — VERIFIED | D05 authenticity/checksum/decoder teruji; PDF watermark deferred dan legacy unverifiable dilabeli; tidak mengklaim transform resilience. |
| 11 | Preview merepresentasikan state relevan dan tidak mengubah business data. | PARTIAL — VERIFICATION PENDING | P4 HTTP/Livewire state dan no business mutation lulus; visual/browser parity belum. |
| 12 | Export completion, download, expiry, dan cleanup sesuai keadaan berkas. | PASS — VERIFIED | P5 artifact completion/download/expiry/cleanup fixture teruji. D12 non-snapshot dan operator scheduler tetap terpisah. |
| 13 | Test yang rusak, placeholder, dan stale references telah ditangani; focused/full-suite results tersedia. | PARTIAL — VERIFICATION PENDING | 434 tes lulus; encoding/stale/placeholder yang dicari ditangani. F34 masih gap pengujian actual Page save. |
| 14 | Browser dan visual verification manual selesai untuk flow yang terdampak. | PARTIAL — VERIFICATION PENDING | Browser capability tidak tersedia; viewport, keyboard, zoom, loader, map, MFA/modal/preview manual belum. |
| 15 | Konfigurasi dan prosedur production-like telah diperiksa, termasuk worker/scheduler dan origin TLS. | PARTIAL — VERIFICATION PENDING | Worker/scheduler/storage/origin TLS/proxy/cookie/Turnstile target belum diuji; build hanya ValidateOnly. |
| 16 | Recovery DB–media–key–config telah dibuktikan pada lingkungan terisolasi. | PARTIAL — VERIFICATION PENDING | P8 memulihkan subset DB+file; tidak memulihkan/test key/config identity/dekripsi sebagai satu unit penuh. Item 16 belum terpenuhi. |
| 17 | Dokumentasi, runbook, dan Guidebook 01–08 konsisten dengan behavior final; isi rahasia tetap ditangani terpisah. | PARTIAL — VERIFICATION PENDING | Dokumen aktif dikoreksi dan matriks baru tersedia; Guidebook08 hanya custody, keputusan owner dan acceptance kebutuhan historis belum final. |
| 18 | Audit asli tetap menjadi baseline historis dan tidak ditulis ulang untuk menghilangkan temuan. | PASS — IMPLEMENTED / EVIDENCE SUFFICIENT | Hash audit historis dipertahankan; file audit tidak diedit. |
| 19 | Tidak ada klaim performa, keamanan, visual, atau recovery yang melampaui hasil verifikasi. | PASS — IMPLEMENTED / EVIDENCE SUFFICIENT | Semua klaim dipagari jenis bukti; source inspection bukan browser/performance/production proof. |
| 20 | Selama bukti manual belum tersedia, status tetap **VERIFICATION PENDING**, bukan selesai. | PASS — IMPLEMENTED / EVIDENCE SUFFICIENT | Gate manual/operasional tetap pending; status rilis diblokir F22, bukan dilabeli selesai. |

DoD14,15,16 tidak terpenuhi. DoD7 blocked material. Penyusunan semua matriks selesai tidak sama dengan semua kondisi DoD lulus.

## 10. Schema / migration review

Tiga migration baru remediation diperiksa sebagai source dan terdapat di catalog DB disposable setelah suite:
- database/migrations/2026_09_23_000001_add_media_processing_attempt_state.php — nullable token/status/error; compatible additive state, tidak backfill original.
- database/migrations/2026_09_23_000002_add_media_cleanup_and_reference_guards.php — nullable cleanup state/index dan 7 PostgreSQL triggers pada Page/News/Gallery/Document/Location FK. JSON/settings memakai coordinator aplikasi, bukan otomatis trigger JSON.
- database/migrations/2026_09_25_000001_add_verified_export_lifecycle.php — export state/format/size/hash/verified/failure serta export_chunks unique(export_id,page).

Catalog confirms media_derivatives unique(media_id,derivative_type): first PUBLIC lookup punya uniqueness di DB, bukan asumsi first berarti pointer. Activation updateOrCreate mengganti isi row yang sama. Delapan slug constraints cocok matcher exact table_slug_unique. Singleton Admin tetap supported command-only.

Migration down menghapus kolom/state/trigger dan bukan rollback release rutin. Fresh isolated test migrations tidak membuktikan upgrade pada dataset legacy produksi. Tidak ada migration yang dibuat/diedit atau dijalankan pada working DB selama P9.

## 11. Route / runtime / build / security boundaries

Source menu index EditMenu, media.derivative, admin.media.original, documents.download/preview, admin.preview.shell/show/asset, admin.exports.pdf.download ditemukan dalam cache tes baru; vendor Filament resources/export routes terbangun bersama total78. Normal route cache masih stale ListMenus dan tidak disentuh. Target config/route/view caches harus dibangun dari release/environment yang benar; jangan salin workstation cache.

Security E01/E03/E05 memeriksa secret profile, MFA, forced password, session expiry/revocation, persistent Livewire, Turnstile fail-closed, sanitizer, headers, upload, preview/media/Document/export auth. CSP global **report-only**; response-specific sandbox dan nosniff dipertahankan. No claim production TLS/proxy/Cloudflare security verified. Admin-original/public derivative no-store/private final headers diuji dalam suite, tidak menghapus cached copies eksternal.

Build ValidateOnly bukan compilation. Wrapper tidak menawarkan output isolated; kompilasi tidak dilakukan karena akan menimpa current public/build. Lockfiles tidak diubah, node_modules/vendor tidak diedit. Browser bootstrap tidak menemukan browser; tidak ada screenshot/viewport/keyboard/zoom evidence.

## 12. Operational / format / recovery gates

| Area | Klasifikasi | Bukti / tindakan berikut |
|---|---|---|
| PostgreSQL schema, model transactions tested paths | VERIFIED IN DISPOSABLE ENVIRONMENT | E02/E03/E05; F22 tetap exception material. |
| Cache/session database adapter | VERIFIED IN DISPOSABLE ENVIRONMENT | P6DatabaseBackendsTest; settings tests sebagian array bukan invalidation multiprocess production. |
| Queue exporter execution | VERIFIED IN DISPOSABLE ENVIRONMENT | Job chain fixture; daemon restart/timeouts/permissions PRODUCTION-LIKE VERIFICATION PENDING. |
| Media processing dispatch | DOCUMENTED ONLY (deployment) | Caller dispatchSync; image fixtures tested; PHP/request timeout target belum. |
| Hourly scheduler | DOCUMENTED ONLY | routes/console.php registered; real cron/overlap/last-run production pending. |
| Private export/media storage permissions | PRODUCTION-LIKE VERIFICATION PENDING | Filesystem fakes tidak membuktikan Linux mount/ACL/rename behavior. |
| Node/Vite | PRODUCTION-LIKE VERIFICATION PENDING | Node/npm available, guard ValidateOnly passed; real build G-BUILD. |
| HEIF decoder | PRODUCTION-LIKE VERIFICATION PENDING | heif-convert tidak ditemukan pada PATH; tidak memproses HEIC. |
| PDF/DOCX/XLSX | VERIFIED IN DISPOSABLE ENVIRONMENT | Valid fixture bytes, parser, MIME, auth, checksum; bukan corpus produksi lengkap. |
| DOC/XLS | PRODUCTION-LIKE VERIFICATION PENDING | Tidak ada fixture asli di tests; parser/allowlist source bukan acceptance proof. |
| TLS/proxy/secure cookies/Turnstile hostname | PRODUCTION-LIKE VERIFICATION PENDING | App simulated header/auth tests + runbook; tidak menghubungi production. |
| Route/config cache target | PRODUCTION-LIKE VERIFICATION PENDING | Temporary route cache pass; normal stale gate tetap. |
| Origin legacy revoke | PRODUCTION-LIKE VERIFICATION PENDING | /storage/media/** dan /storage/originals/** harus ditolak origin, route controlled tetap jalan. |
| Logs/rotation/monitoring | DOCUMENTED ONLY | Exception/failed job/state source tersedia; F30 gap dan production visibility tetap. |
| Recovery | VERIFIED IN DISPOSABLE ENVIRONMENT — subset only | P8 DB/fixture PDF/hash/Document resolve; seluruh media/key/config/secret custody/domain/worker restore belum. |

P8 tidak cukup untuk DoD16: dua Media rows lain tidak disertai file restore, secret/key/config hanya didokumentasikan dependency-nya, dekripsi MFA dan watermark historical key setelah restore belum diuji. Ini partial technical recoverability, bukan full disaster recovery.

D07: route aplikasi terkontrol teruji; legacy origin **OPERATIONAL GATE PENDING**. Safe rollback wajib mempertahankan penolakan legacy URL; tidak ada perpindahan file/deploy/origin modification P9.

## 13. F39–F43 / UI / technical debt

- F39 current query bounds diuji lagi dalam full suite; P9 tidak mengarang angka before/after baru atau latency gains. Controlled route tetap menjadi pemeriksa file/status.
- F40 file size/row limits sudah ada. Full-size image sinkron, in-memory PDF sampai1000 rows, map semua lokasi dan dependensi CDN tetap theoretical/production-like concern; belum stress benchmark. Penundaan membutuhkan acceptance workload/risiko, tidak otomatis closed.
- F41 source loader/parent keyboard concern masih ada; actual reproduction pending. Jangan membuka kembali sidebar15rem/menu columns yang sudah diklasifikasikan stale test tanpa bukti desain/browser baru.
- F42 accumulation/dedup/bound20 provider teruji; tidak ditulis ulang P9.
- F43: CreateMenu **ACTIVE** (route + tests), PublicController **ACTIVE** (home/preview), smalot/pdfparser **ACTIVE** (MediaInputPolicy/ExportArtifactService). PagesTable, MediaController/MediaProcessingService, UserFactory **AMBIGUOUS — no active registration/reference found in inspected runtime paths**, retained. Tidak ada dependency/code removal. Featured/optimization gaps ada pada requirements, bukan bukti bahwa field boleh dihapus.

## 14. Documentation alignment dan koreksi P9

Canonical PROJECT_STATE kini mengakui defect F22/F34/F24/F30, bukan menyebut implementasi seluruhnya selesai. README merujuk status P9. DOCUMENT_STATUS menambahkan matriks final/traceability.

Dua kontradiksi P8 dikoreksi secara sempit: ProcessMediaJob implements ShouldQueue tetapi semua caller dispatchSync; social Twitter/YouTube belum ikon publik otomatis. Queue runbook, runtime prerequisites, local guide, Guidebook03/04 diselaraskan. Dokumen historical tetap ditandai, audit tidak diedit. Guidebook08 isi tidak diinspeksi.

## 15. Static checks, safety, limitations

- No obsolete Filament Tables Actions/ListMenus in active app/routes/tests references inspected; ListMenus tersisa hanya normal cache/history/reports where appropriate.
- PDF startxref yang ditemukan ada di test fixture construction, bukan active regex watermark injector.
- Targeted tests search tidak menemukan assertTrue(true), synthetic Route::get/post/put/delete registration, atau global withoutMiddleware() baru. Ini targeted quality check, bukan proof semua assertions memadai; F34 tetap terbuka.
- DOC/XLS/HEIC genuine fixtures tidak ditemukan; no fake magic-header acceptance claim.
- Known public original/static URLs tidak ditambahkan P9; P3 compatibility public disk tetap source legacy-origin gate.
- Diff/ignore/path/secret-value checks dilakukan pada P9 artifacts; tracked .env/testing/guardrails/public hot/testing storage/vendor checks kosong. Tidak membaca nilai secret handover/history.
- git diff --check exit0 (peringatan CRLF pada Blade pra-P9 bukan whitespace errors).
- No application PHP/test/support edited, sehingga php -l baru tidak diperlukan; full suite meload source/tests.
- Index diff SHA256 akhir cocok baseline dan SHA256 log cocok bukti E-FULL. Perubahan yang dilakukan P9 hanya pada sepuluh dokumen di bawah; tidak ada application/test/audit/lockfile/cache edit. Manifest source hash awal tidak dipersist, sehingga tidak diklaim sebagai final whole-tree checksum proof. Ignored full-suite log dipertahankan; temporary route cache dibersihkan. pg_ctl status akhir: no server running pada cluster disposable; no production/user DB/file mutation, stage, commit, normal cache regeneration, worker/scheduler/deploy.

File P9 (repository-relative): `README.md`; `docs/PROJECT_STATE.md`; `docs/DOCUMENT_STATUS.md`; `docs/FINAL_REMEDIATION_CLOSURE_2026-09-25.md`; `docs/REQUIREMENT_TRACEABILITY.md`; `docs/LOCAL_DEVELOPMENT.md`; `docs/operations/QUEUE_AND_SCHEDULER.md`; `docs/operations/RUNTIME_PREREQUISITES.md`; `docs/guidebook/03_KELOLA_WEBSITE_DAN_TAMPILAN.md`; `docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md`.

## 16. Actionable release gates

1. **R-CODE:** satu corrective unit terikat F22/F34: aktifkan logical transaction Page create/edit dan uji child failure melalui editor aktual. Sertakan F24 consumer settings dan F30 event audit yang sudah terkonfirmasi sebagai scope terpisah jelas di unit yang sama; jangan broad refactor.
2. **R-REQ:** ratifikasi/implementasikan gap FR-021 featured Page/Gallery, NFR-015/NFR-029 optimization, NFR-033 version compatibility, dan intent NFR-031. D14 tidak membolehkan silent requirement removal; acceptance penundaan belum ada.
3. **G-OWNER:** putuskan D01/D02/sisa D08/D09/D10/D12/D15 beserta impact/acceptance; no inferred backup/purge policy.
4. **G-FORMAT:** genuine DOC/XLS acceptance corpus dan HEIC decoder fixtures di lingkungan terisolasi; legacy reconciliation dengan operator.
5. **G-BROWSER:** download loader, map descendant Enter/Space/focus, menu/preview/MFA/modals, mobile/tablet/desktop, zoom/long labels/empty/error/loading states dengan browser nyata.
6. **G-BUILD:** guarded compilation ke release/disposable artifact yang aman, verifikasi manifest/assets dan hot absent.
7. **G-ORIGIN:** operator cutover legacy deny namespaces, uji old active/archived/original URLs dan controlled route dari origin nyata; jangan buka kembali pada rollback.
8. **G-OPS:** target worker/scheduler/permissions/timeouts/TLS/proxy/cookies/Turnstile/cache/log visibility; normal ListMenus cache harus regenerated di target.
9. **G-RECOVERY:** restore DB+seluruh referenced files+keys/config identity yang konsisten, uji dekripsi/verification/domain/services sesuai policy D09; bukan hanya fixture subset.
10. Setelah perubahan source yang diotorisasi, focused regression lalu guarded final regression sesuai scope; tidak menjalankan loop perbaikan di P9 ini.

## 17. Final decision

**Implementation INCOMPLETE**: source punya mayoritas remediation dan full-suite regression evidence, tetapi F22 active transaction invariant hilang, F34 test gap, F24/F30 serta requirement consumer/optimization/version belum lengkap. Status historis fase tidak mengatasi bukti source terbaru.

**RELEASE BLOCKED BY MATERIAL DEFECT** terutama F22; browser/origin/operational/owner gates juga tetap terbuka. Audit closure P9 selesai dalam arti semua finding/requirement/DoD mempunyai keputusan berbukti. Tidak ada acceptance risiko atas nama owner dan tidak ada fase corrective berikutnya dimulai.

## Adendum pasca-P9 — koreksi material 26 September 2026

Adendum ini menggantikan **status terkini** empat finding di atas; uraian P9 sebelumnya tetap dipertahankan sebagai rekam bukti historis. Lingkungan: PostgreSQL disposable `village_cms_test` pada loopback `127.0.0.1:5434`; guardrail `safe-test.ps1 -ValidateOnly` lulus; disk tes local/public/private/admin_exports memakai `storage/framework/testing` tanpa symlink keluar. Tidak ada DB atau media kerja yang disentuh.

| Finding | Status terkini | Bukti dan batas |
|---|---|---|
| F22 | VERIFIED FIXED | `CreatePage` dan `EditPage` mengaktifkan transaksi Filament yang meliputi penyimpanan parent, relasi, dan hook `PageBuilderService`. Tes Livewire aktual menyuntik kegagalan setelah penulisan komponen dan membuktikan rollback parent/section/component; save sukses mempertahankan ID, layout, visibility, settings, dan audit. Bukan bukti rollback filesystem. |
| F24 | VERIFIED FIXED untuk gap social consumer yang dikonfirmasi | `social_twitter` dan `social_youtube` kini dibaca footer publik dan preview overlay; URL tetap melewati `ContentSecurity`, output escaped, nilai kosong/unsafe tidak ditampilkan. Tes WebsiteSettings save/reload, HTTP publik, dan preview tanpa mutasi lulus. `notification_email` tetap D10; consumer featured dan optimisasi lain masih gap requirement terpisah. |
| F30 | VERIFIED FIXED untuk cakupan event aplikasi yang dikonfirmasi | Page create/edit/archive/restore/delete, Menu create/edit/delete, Media upload/edit/processing/archive/restore/delete/verification, auth Login/Logout/Failed, password dan MFA state memakai outcome serta redaksi yang sesuai. Page/Menu satu event bisnis per action; stale media job tidak mencatat sukses. Tes audit baru dan grup security/media lulus. Immutability hanya pada policy/model aplikasi, bukan klaim append-only terhadap DBA. Retensi manual D08 dan observabilitas produksi tetap gate. |
| F34 | VERIFIED FIXED untuk test fidelity Page | `PostP9PageBoundaryTest` memanggil CreatePage/EditPage Livewire aktif **tanpa outer transaction dari harness**, menguji kegagalan child setelah write nyata, audit rollback, dan roundtrip identitas. Tes lama yang hanya membuktikan service tetap dibatasi pada klaim semula. |

**Matriks finding terkini:** VERIFIED FIXED **22**; OPERATIONAL GATE PENDING **2**; OWNER DECISION PENDING **1**; PARTIALLY VERIFIED **15**; REOPEN REQUIRED **0**; DOCUMENT-ONLY RESOLUTION **3**; BROWSER GATE PENDING **2**; DEFERRED — ACCEPTANCE REQUIRED **1**. Total tetap **46**. Revisi ini tidak menaikkan status finding lain.

**Regression sebelum verifikasi final:** tes terfokus baru `PostP9PageBoundaryTest` 3/3 (29 assertion) dan `PostP9AuditSettingsTest` 4/4 (43 assertion) lulus. PageBuilderService 4/4, P2DataInvariant 7/7, P4ANavigation 6/6, WebsiteSettingsPreview 1/1, P3AMediaLifecycleVerification 9/9, SecurityExtended 15/15, SecurityTest 16/16 lulus. Full suite interim berjalan 440 lulus/1 gagal (2.781 assertion; 83,81 detik). Kegagalan `AdminTableExportTest > pdf table action redirects...` mengharapkan total audit tetap bertambah satu setelah `auth()->logout()`; listener logout baru menambah event yang valid. Tes itu dikoreksi hanya pada assertion terakhir agar menghitung `export_completed` dan `admin_logout` masing-masing satu; rerun terfokus 10/10 (421 assertion) lulus. Hasil itu kemudian digantikan oleh full suite final 441/441 pada bagian verifikasi final di bawah.

**DoD terkini:** item **7 PASS — VERIFIED** untuk kegagalan DB pada Page editor aktual; item **13 PASS — VERIFIED** karena fidelity Page dan final full suite pascakoreksi lulus. Item 14/15/16/17 tetap parsial. Item 1 tetap parsial karena gate/acceptance final; item 20 tetap PASS dengan release verification pending. Requirement FR-010 dan NFR-009 naik menjadi SATISFIED — VERIFIED dalam batas tes aplikasi disposable; jumlah requirement menjadi 24 verified, 34 partially verified, dan kategori lain tidak berubah (total 85). FR-009/FR-016/BR-006 serta NFR-036 tetap parsial karena gate tersendiri yang dicatat pada matriks requirement.

**Gap implementasi di luar empat koreksi:** FR-021 featured Page/Gallery belum memiliki consumer publik yang disahkan; NFR-015/NFR-029 image optimization/resize/thumbnail belum pipeline aktif; NFR-033 kompatibilitas versi algoritma/key belum ada; NFR-031 background processing masih `dispatchSync` pada caller aktif. Tidak ada persetujuan untuk menghapus kebutuhan ini. Publication scheduling roundtrip dan beberapa keputusan owner juga belum dituntaskan. Maka **IMPLEMENTATION INCOMPLETE** masih akurat walaupun empat defect P9 telah dikoreksi.

**Gate rilis:** cutover origin legacy D07, browser F41 dan parity visual, fixture DOC/XLS dan HEIC, target worker/scheduler/storage/TLS/proxy/cache, recovery DB+file+key+config, build aman, dan keputusan D01/D02/sisa D08/D09/D10/D12/D15 tetap terbuka. Source koreksi tidak menggantikan verifikasi tersebut. **RELEASE VERIFICATION PENDING**; tidak ada klaim release verified atau production deployment.

### Verifikasi full-suite final pasca-koreksi

Perintah yang dijalankan satu kali melalui guardrail:

```powershell
$env:APP_ENV='testing'
$env:APP_ROUTES_CACHE='storage/testing/post-corrective-routes.php'
$env:APP_CONFIG_CACHE='storage/testing/post-corrective-config.php'
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot .
```

Hasil authoritative pasca-koreksi: **441 passed, 0 failed, 2.782 assertions, 81,83 detik**; skipped/incomplete tidak dilaporkan. Guardrail `-ValidateOnly` sebelumnya lulus untuk `APP_ENV=testing`, PostgreSQL disposable `village_cms_test` pada `127.0.0.1:5434`, queue sync dan disk test yang terisolasi. Cluster test dihentikan setelah verifikasi. Ini menggantikan hasil interim 440/1 dan mengangkat DoD13 menjadi PASS — VERIFIED. Tidak mengubah status requirement yang belum diimplementasikan atau gate operasional.

### Klasifikasi gap sumber yang tersisa

| Item | Klasifikasi | Alasan dan batas keputusan |
|---|---|---|
| FR-021 featured Page/Gallery | IMPLEMENTATION REQUIRED BY APPROVED D14 | Toggle dan teks UI aktif menjanjikan penonjolan publik, tetapi hanya News yang dikonsumsi controller beranda. Tentukan penempatan/urutan Page dan Gallery sebelum implementasi kecil berikutnya. |
| NFR-015 optimization | IMPLEMENTATION REQUIRED BY APPROVED D14 | Derivative saat ini tervalidasi, tetapi tidak resize/compress/thumbnail. Memilih ukuran/format/kualitas serta compatibility lama membutuhkan keputusan produk terikat D14. |
| NFR-029 verify after optimization | DEPENDENT IMPLEMENTATION | Tidak dapat dipenuhi sebelum NFR-015; checksum/verifikasi final saat ini tidak membuktikan verifikasi setelah tahap optimasi yang belum ada. |
| NFR-033 algorithm version compatibility | OWNER/ARCHITECTURE ACCEPTANCE REQUIRED | Verifier menerima literal 1.0 dan satu key. Dispatch versi, key ID/history, migration/reverification memiliki dampak key/storage dan tidak boleh diinferensikan. |
| NFR-031 background media processing | CANDIDATE FOR EXPLICIT DEFERRED ACCEPTANCE | Requirement lama menyebut queue, tetapi Master §8.1 memperbolehkan sinkron tahap awal dan meminta bukti kebutuhan/kontrak feedback sebelum async. `ShouldQueue` + `dispatchSync` bukan implementasi background. |
| F21 scheduling publication | OWNER DECISION / FOCUSED VERIFICATION | Scope D11 benar membaca state persisted. Namun Page dan `HasContentLifecycle` mengisi `published_at=now()` ketika status berubah ke published. Tidak ada bukti workflow editor wajib mendukung schedule future; owner perlu memilih apakah schedule adalah capability produk, lalu lakukan tes/correction terikat. |

Audit pasca-F30: FR-019 tetap VERIFIED untuk tampilan audit; NFR-009 VERIFIED dalam boundary aplikasi; BR-006 tetap PARTIALLY VERIFIED karena retensi/observabilitas produksi bukan bukti source. Tidak ada klaim append-only terhadap DBA.

## Adendum paket implementasi keputusan pemilik — 26 September 2026

FR-021: Page unggulan tampil dalam bagian kecil beranda; Gallery unggulan diprioritaskan dalam kapasitas kisi album. Keduanya memakai scope publik D11, urutan stabil, batas hasil, dan URL media terkontrol. NFR-015/NFR-029: kandidat gambar JPEG/PNG/WebP dioptimasi secara privat tanpa upscale, thumbnail 480×270 dibuat, dan final byte tiap derivative ditandatangani serta diverifikasi sebelum aktivasi. Original dan active generation terdahulu tetap dipertahankan bila proses gagal. F21: `published_at` yang disengaja, termasuk waktu masa depan, tidak ditimpa saat transisi terbit; form admin menyediakan jadwal WIB. D15: sandi baru minimal 12 karakter dengan huruf besar/kecil dan angka, Argon2id untuk hash baru, limiter progresif, serta sandi saat ini dan TOTP pada setiap perubahan kredensial/profil sensitif. Bukti cakupan ini ada pada tes terfokus `OwnerApprovedFeaturedSchedulingTest`, `OwnerApprovedMediaOptimizationTest`, `OwnerApprovedSecurityPolicyTest`, dan grup regresi lintas fase.

Guardrail `safe-test.ps1 -ValidateOnly` lulus. Satu full suite `safe-test.ps1 -ProjectRoot .` menghasilkan **443 pass / 8 fail / 2.792 assertion / 733,32 detik**. Enam file test historis yang gagal dikoreksi hanya pada ekspektasi keputusan baru; rerun masing-masing lulus **53/53 tes, 424 assertion**. Tes tambahan mendapati query beranda mengambil empat album ketika kisi lama hanya merender tiga; query dan batas tampilan diselaraskan tanpa mengubah layout. Grup featured/jadwal termasuk form admin dan batas tiga album kini lulus **4/4, 30 assertion**. Hasil ini **bukan** full-suite hijau baru; satu guarded full rerun masih diperlukan pada gate verifikasi berikutnya. DoD item 13 untuk *paket terbaru* tetap **PARTIAL — VERIFICATION PENDING**, sementara hasil 441/441 sebelum paket tetap bukti historis yang sah.

Tidak ada migrasi baru atau backfill data pada paket ini. NFR-031 (`dispatchSync`) dan NFR-033 (algoritma 1.0/satu trusted key) diterima sebagai penundaan eksplisit, bukan defect paket. D01/D02/D08/D09/D10/D12/D14/D15 diselesaikan sebagai keputusan pemilik dalam [kontrak 26 September](OWNER_APPROVED_DECISIONS_2026-09-26.md). Implementasi source paket sudah tersedia, tetapi D02 recovery operator yang aman, backup D09, HEIC, DOC/XLS nyata, browser/responsif, build terisolasi, origin D07, dan verifikasi produksi tetap gate operasional. **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS** dalam batas source paket; **RELEASE VERIFICATION PENDING**.
