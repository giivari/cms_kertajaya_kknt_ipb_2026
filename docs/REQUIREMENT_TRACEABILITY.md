# Requirement traceability — P9, 25 September 2026

> **Refresh D02 — 26 September 2026:** keputusan pemulihan MFA oleh operator tanpa recovery codes kini mempunyai command `admin:recover-mfa`, runbook, dan bukti disposable. Enam tes D02 (43 assertion), 65 tes auth/audit terkait (380 assertion), serta guarded full suite **459 passed / 0 failed / 2.924 assertions / 573,83 detik** lulus. Ini baseline otomatis terbaru; 453/453 di bawah adalah snapshot sebelum D02. Ketersediaan CLI dan walkthrough di target, termasuk recovery lengkap D09, tetap verifikasi operasional terpisah. Tidak ada endpoint pemulihan publik atau reset password.

> **Baseline otomatis authoritative — 26 September 2026:** guarded full suite lulus **453 passed / 0 failed / 2.881 assertion / 192,93 detik** setelah koreksi paket owner-approved. FR-021, NFR-015, NFR-029, F21, dan D15 sekarang memiliki bukti suite penuh dalam batas aplikasi terisolasi. NFR-031 dan NFR-033 tetap deferred yang disetujui; gate browser, origin, format runtime, build, dan operasi target tidak dipromosikan menjadi verified.

> **Bukti paket terkini:** FR-021, NFR-015/NFR-029, F21, dan D15 telah memperoleh source dan tes terfokus; NFR-031/NFR-033 ditunda dengan persetujuan pemilik. Satu full suite paket masih menghasilkan **443 pass/8 fail** sebelum enam file test ekspektasi lama dikoreksi; file-file itu sekarang lulus 53/53 tes terfokus, tetapi belum ada full rerun. Label status baris di bawah merujuk bukti sempit yang disebut pada tiap baris; ringkasan jumlah P9 tetap historis, bukan penghitungan ulang paket ini.

> **Keputusan dan implementasi 26 September 2026:** lihat [kontrak pemilik](OWNER_APPROVED_DECISIONS_2026-09-26.md). Baris FR-008/FR-021/NFR-006/NFR-015/NFR-029/NFR-031/NFR-033 di bawah diperbarui; ringkasan jumlah P9 dan kalimat lama di bagian lain tetap snapshot historis sampai full suite paket selesai. F21 mempertahankan jadwal masa depan tanpa backfill. Release tetap menunggu gate eksternal.

> **Pembaruan pasca-P9 (26 September 2026):** F22/F24/F30/F34 dikoreksi dan lulus tes terfokus. Full suite guarded setelah assertion audit ekspor diperbaiki lulus **441/441, 2.782 assertion, 81,83 detik**. Bagian P9 yang menyebut gap empat finding itu adalah snapshot sebelum koreksi. [Adendum closure](FINAL_REMEDIATION_CLOSURE_2026-09-25.md) adalah status terkini.

Status keputusan dan bukti mengacu [laporan closure P9](FINAL_REMEDIATION_CLOSURE_2026-09-25.md). Ini rekonsiliasi, bukan perubahan requirement atau persetujuan rilis.

## Sumber dan kelengkapan

Ditemukan **85 ID unik eksplisit**: 47 pada [SRS](<DOKUMEN INISIASI PROYEK.txt>) (FR-001–022, NFR-001–015, BR-001–010), dan 38 pada [Watermark](Watermark.txt) (FR-035–048, NFR-026–037, BR-021–032). Pencarian ID pada dokumen requirement lokal menemukan dua sumber ini; PRD/Revisi memberikan interpretasi tambahan, bukan ID baru. Tidak ada ID yang dikarang untuk FR-023–034, NFR-016–025, atau BR-011–020. Penanda historis P8 tidak menghapus kebutuhan; approved owner decisions dapat mengganti kontrak lama secara eksplisit.

Setiap baris di bawah mempunyai source+nomor baris, kebutuhan asli, interpretasi, keputusan, pemetaan implementasi/tes/dokumen melalui grup, status, dan gate. Grup adalah rujukan teknis, **bukan** ID requirement baru. Status SATISFIED — VERIFIED berarti requirement sempit yang disebut teruji dalam lingkungan disposable, bukan keseluruhan deployment.

## Jumlah per status

| Status | Jumlah |
|---|---:|
| SATISFIED — VERIFIED | 24 |
| SATISFIED — PARTIALLY VERIFIED | 34 |
| OWNER DECISION PENDING | 3 |
| IMPLEMENTED — MANUAL VERIFICATION PENDING | 10 |
| DEFERRED | 1 |
| NOT IMPLEMENTED | 3 |
| SUPERSEDED BY APPROVED DECISION | 10 |

## Katalog implementasi, pengujian, dokumentasi

Katalog asal masuk full suite P9 (434 lulus, 2.688 assertion, 91,45 detik); tes pasca-P9 bertambah di grup builder/audit/settings. Penyebutan file tes tidak berarti semua aspek requirement telah dicakup. Bukti builder lama hanya membuktikan transaksi yang diberikan harness; `PostP9PageBoundaryTest` kini membuktikan boundary CreatePage/EditPage aktif tanpa transaksi test-supplied.

| Grup | Implementasi source (repository-relative) | Bukti tes / bukti lain | Dokumentasi |
|---|---|---|---|
| public | routes/web.php; app/Http/Controllers/PageController.php | tests/Feature/HttpRoutesVerificationTest.php; tests/Feature/PageTest.php | docs/guidebook/05_PANDUAN_TAMPILAN_PUBLIK.md |
| auth | app/Models/Admin.php; app/Filament/Pages/Auth/Login.php; app/Filament/Pages/Auth/EditProfile.php; app/Providers/Filament/AdminPanelProvider.php | tests/Feature/SecurityTest.php; tests/Feature/SecurityExtendedTest.php; tests/Feature/P1ASecurityBoundaryTest.php | docs/guidebook/01_PENGANTAR_DAN_AKSES_ADMIN.md |
| search | app/Http/Controllers/Public/SearchController.php | tests/Feature/PublicSearchTest.php; tests/Feature/P2DataInvariantTest.php | docs/guidebook/05_PANDUAN_TAMPILAN_PUBLIK.md |
| contact | app/Http/Controllers/Public/ContactController.php; app/Filament/Resources/ContactMessageResource.php | tests/Feature/ContactMessageTest.php; tests/Feature/ContactMessageSecurityTest.php | docs/PROJECT_STATE.md |
| builder | app/Filament/Resources/Pages/Pages/CreatePage.php; app/Filament/Resources/Pages/Pages/EditPage.php; app/Services/PageBuilderService.php | tests/Feature/PageBuilderServiceTest.php; tests/Feature/P2DataInvariantTest.php; tests/Feature/PostP9PageBoundaryTest.php; tests/Feature/PageComponentRenderTest.php | docs/guidebook/02_KELOLA_KONTEN.md |
| news | app/Filament/Resources/News; app/Http/Controllers/Public/NewsController.php | tests/Feature/NewsTest.php; tests/Feature/AdminContentBatchTwoTest.php | docs/guidebook/02_KELOLA_KONTEN.md |
| gallery | app/Filament/Resources/GalleryAlbums; app/Services/ScopedPositionService.php | tests/Feature/GalleryTest.php; tests/Feature/P2DataInvariantTest.php | docs/guidebook/02_KELOLA_KONTEN.md |
| doc | app/Services/DocumentFilePolicy.php; app/Services/DocumentDeliveryService.php; app/Services/DocumentReferenceCoordinator.php; resources/views/pages/components/documents.blade.php | tests/Feature/P3CManagedDocumentTest.php; tests/Feature/DocumentTest.php | docs/guidebook/02_KELOLA_KONTEN.md |
| media | app/Jobs/ProcessMediaJob.php; app/Services/MediaDeliveryService.php; app/Services/MediaDeletionService.php | tests/Feature/P3AMediaLifecycleVerificationTest.php; tests/Feature/P3AMediaReferenceConcurrencyTest.php | docs/operations/MEDIA_LEGACY_CUTOVER.md |
| watermark | app/Services/WatermarkService.php; app/Services/WatermarkVerificationService.php; app/Jobs/ProcessMediaJob.php | tests/Feature/P3BWatermarkIntegrityTest.php; tests/Feature/WatermarkVerificationTest.php; tests/Feature/WatermarkKeyRotationTest.php | docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md |
| manual | app/Filament/Resources/Media/Tables/MediaTable.php; app/Services/WatermarkVerificationService.php | tests/Feature/P3BWatermarkIntegrityTest.php | docs/guidebook/04_MANAJEMEN_MEDIA_DAN_KEAMANAN.md |
| nav | app/Services/NavigationResolver.php; app/Filament/Resources/Menus | tests/Feature/P4ANavigationTest.php; tests/Feature/MenuTest.php | docs/guidebook/03_KELOLA_WEBSITE_DAN_TAMPILAN.md |
| settings | app/Filament/Pages/WebsiteSettings.php; app/Services/SettingsService.php; resources/views/partials/footer.blade.php | tests/Feature/SettingsTest.php; tests/Feature/P2DataInvariantTest.php; tests/Feature/WebsiteSettingsPreviewTest.php; tests/Feature/PostP9AuditSettingsTest.php | docs/guidebook/03_KELOLA_WEBSITE_DAN_TAMPILAN.md |
| map | app/Http/Controllers/Public/MapController.php; app/Filament/Resources/Locations; resources/js/maps.js | tests/Feature/PublicMapTest.php | docs/guidebook/05_PANDUAN_TAMPILAN_PUBLIK.md |
| audit | app/Models/Concerns/Auditable.php; app/Models/AuditLog.php; app/Filament/Resources/AuditLogs; app/Providers/AppServiceProvider.php | tests/Feature/AuditTest.php; tests/Feature/AuditLogImmutabilityTest.php; tests/Feature/P1ASecurityBoundaryTest.php; tests/Feature/PostP9AuditSettingsTest.php; tests/Feature/PostP9PageBoundaryTest.php | docs/PROJECT_STATE.md |
| preview | app/Filament/Support/PreviewStateNormalizer.php; app/Services/Preview/PreviewTokenStore.php; app/Support/Preview | tests/Feature/P4BPreviewIntegrationTest.php; tests/Feature/PreviewTokenStoreTest.php; tests/Feature/PagePreviewTest.php | docs/operations/PREVIEW.md |
| ui | resources/views/layouts/public.blade.php; resources/css/app.css; resources/js/maps.js | tests/Feature/PhaseTwoVisualTest.php; tests/Feature/B12BThemeModeTest.php (source/HTML only) | docs/UI System Design Core.txt |
| ops | config/queue.php; config/session.php; config/filesystems.php; bootstrap/app.php | tests/Feature/P6DatabaseBackendsTest.php; P8 representative rehearsal | docs/operations/RUNTIME_PREREQUISITES.md |
| single | app/Console/Commands/ProvisionAdmin.php; app/Models/Admin.php | tests/Feature/P2SingleAdminConcurrencyTest.php; tests/Feature/SecurityTest.php | docs/operations/RECOVERY.md |

## 85 requirement eksplisit

| ID | Sumber:baris | Kebutuhan asli | Interpretasi bisnis saat ini | Keputusan | Grup source/tes/docs | Status | Gate / batas bukti |
|---|---|---|---|---|---|---|---|
| FR-001 | SRS:300 | Guest dapat mengakses website tanpa login. | Guest mengakses route publik tanpa akun. | — | public | SATISFIED — VERIFIED | Hanya data eligible yang boleh tampil. |
| FR-002 | SRS:302 | Guest dapat melihat halaman publik. | Halaman sesuai publication predicate persisted. | D11 | public | SATISFIED — PARTIALLY VERIFIED | F21 scheduling roundtrip dan browser pending. |
| FR-003 | SRS:304 | Guest dapat mencari informasi. | Judul Page/News/Document, scalar UTF-8 dan batas hasil. | — | search | SATISFIED — VERIFIED | F31/F32 teruji PostgreSQL; bukan full-text engine. |
| FR-004 | SRS:306 | Guest dapat mengirim pesan narahubung. | Pengiriman/persist pesan tersedia; email bukan bagian requirement ini yang teruji. | D10 terkait email | contact | SATISFIED — VERIFIED | Keputusan notifikasi email belum dibuat. |
| FR-005 | SRS:308 | Guest dapat mengunduh dokumen publik. | Dokumen publik tervalidasi melalui controlled download. | D03/D04/D07 | doc | SATISFIED — PARTIALLY VERIFIED | DOC/XLS asli dan cutover legacy pending. |
| FR-006 | SRS:310 | Admin dapat login. | Login password+TOTP aktual, secret tidak masuk profil; pemulihan operator mencabut sesi lama dan mewajibkan enrollment ulang. | D02/D15 kebijakan | auth | SATISFIED — VERIFIED | Pemulihan D02 teruji disposable; sesi/domain target dan walkthrough browser belum. |
| FR-007 | SRS:312 | Admin dapat logout. | Logout mengakhiri sesi pada flow aplikasi. | — | auth | SATISFIED — VERIFIED | Tidak membuktikan semua sesi/perangkat produksi. |
| FR-008 | SRS:314 | Admin dapat mengubah password. | Sandi baru memakai aturan D15 dan profil meminta sandi saat ini serta TOTP aktif. | D15 approved | auth | SATISFIED — VERIFIED | Full suite 459/459 mencakup profil, forced-change, Argon2id dan bcrypt legacy; pemulihan D02 tidak mengubah hash password. |
| FR-009 | SRS:316 | Admin dapat mengelola halaman. | CRUD Page tersedia, parent+child dalam satu logical DB save. | D01/D11 | builder | SATISFIED — PARTIALLY VERIFIED | F22 rollback Filament aktif teruji pasca-P9; browser workflow dan D01 masih gate. |
| FR-010 | SRS:318 | Admin dapat menggunakan page builder. | Builder relational mempertahankan ID/layout/settings dan managed Document. | D13 | builder | SATISFIED — VERIFIED | Create/Edit Filament aktual teruji rollback child setelah write serta roundtrip identitas; browser visual tetap gate terpisah. |
| FR-011 | SRS:320 | Admin dapat mengelola berita. | CRUD berita, sanitizer dan delta kategori tersedia. | D01/D11 | news | SATISFIED — PARTIALLY VERIFIED | Scheduling status transition serta browser pending. |
| FR-012 | SRS:322 | Admin dapat mengelola galeri. | CRUD galeri, ordered items, controlled image URLs. | D07/D14 | gallery | SATISFIED — PARTIALLY VERIFIED | Concurrent reorder, featured consumer, browser belum lengkap. |
| FR-013 | SRS:324 | Admin dapat mengelola dokumen. | CRUD PDF/DOC/DOCX/XLS/XLSX dengan MIME/validasi yang sesuai. | D03/D04/D13 | doc | SATISFIED — PARTIALLY VERIFIED | DOC/XLS genuine corpus dan legacy reconciliation. |
| FR-014 | SRS:326 | Admin dapat mengelola media library. | Upload/edit/reprocess/archive/restore/delete managed Media. | D06/D07/D08 | media | SATISFIED — PARTIALLY VERIFIED | HEIC, writer concurrency lengkap, legacy origin. |
| FR-015 | SRS:328 | Admin dapat mengelola menu. | Header/footer, hierarchy/order/identity, GET read-only. | D11 | nav | IMPLEMENTED — MANUAL VERIFICATION PENDING | HTTP/Livewire lulus; browser editor pending; cache target usang. |
| FR-016 | SRS:330 | Admin dapat mengelola pengaturan website. | Core load-save-reload/preview dan consumer aktif harus konsisten. | D14/D10 | settings | SATISFIED — PARTIALLY VERIFIED | Social Twitter/YouTube kini tampil aman di footer/preview; D10 notification_email dan consumer lain tetap gate. |
| FR-017 | SRS:332 | Admin dapat mengelola lokasi peta. | Lokasi eligible, kategori, koordinat dan detail tersedia. | D11 | map | IMPLEMENTED — MANUAL VERIFICATION PENDING | Keyboard/Leaflet browser F41 pending. |
| FR-018 | SRS:334 | Admin dapat mengelola pesan narahubung. | Inbox admin, lihat dan kelola pesan. | D08/D10 | contact | SATISFIED — VERIFIED | Retensi/contact-email bukan keputusan tes. |
| FR-019 | SRS:336 | Admin dapat melihat audit log. | Admin dapat membaca audit yang tercatat. | D08 | audit | SATISFIED — VERIFIED | Event aplikasi yang dikonfirmasi dicakup pasca-F30; retensi/observabilitas produksi tetap terpisah. |
| FR-020 | SRS:338 | Admin dapat mengarsipkan konten. | Archive menyembunyikan konten/controlled bytes tanpa purge otomatis. | D07/D08/D11 | media | SATISFIED — PARTIALLY VERIFIED | Known legacy URL revocation G-ORIGIN. |
| FR-021 | SRS:340 | Admin dapat menandai konten unggulan. | Featured News tetap ada; Page featured dan Gallery featured dipakai di beranda dengan D11, urutan tetap dan batas hasil. | D14 approved | public | SATISFIED — VERIFIED | Full suite 453/453 mencakup HTTP beranda, batas tiga kartu Gallery, urutan, dan D11; browser responsif pending. |
| FR-022 | SRS:342 | Admin dapat melakukan preview sebelum publikasi. | Preview state relevan tanpa simpan/publish; owner/session-bound. | D01 | preview | IMPLEMENTED — MANUAL VERIFICATION PENDING | Browser parity pending, bukan autosave. |
| NFR-001 | SRS:348 | Website harus responsif. | Layout responsif dipertahankan. | — | ui | IMPLEMENTED — MANUAL VERIFICATION PENDING | Tidak ada browser mobile/tablet/desktop. |
| NFR-002 | SRS:350 | Website harus mobile-first. | Styling mobile-first pada source. | — | ui | IMPLEMENTED — MANUAL VERIFICATION PENDING | Tidak ada bukti viewport/overflow/touch. |
| NFR-003 | SRS:352 | Website harus menggunakan HTTPS. | HTTPS/cookies/HSTS bergantung origin/proxy. | — | ops | IMPLEMENTED — MANUAL VERIFICATION PENDING | TLS/proxy produksi belum diperiksa. |
| NFR-004 | SRS:354 | Password harus disimpan menggunakan hashing. | Password hashed melalui model/Hash. | — | auth | SATISFIED — VERIFIED | Tidak mencetak hash atau credential. |
| NFR-005 | SRS:356 | Login menggunakan password dan TOTP. | Password+TOTP login/enrollment aktif; operator dapat membatalkan enrollment lama tanpa recovery code dan Admin wajib enrollment ulang. | D02 | auth | SATISFIED — VERIFIED | Command dan provider login/enrollment teruji disposable; walkthrough target/browser masih pending. |
| NFR-006 | SRS:358 | Sistem harus memiliki rate limiting login. | D15: delay 1/3/5/10 detik dan lockout 15 menit setelah 5 gagal pada pasangan identitas-klien. | D15 approved | auth | SATISFIED — VERIFIED | Full suite 453/453 mencakup Livewire/limiter dan reset sukses; cache target produksi pending. |
| NFR-007 | SRS:360 | Sistem harus memiliki proteksi CSRF. | CSRF web middleware, actual protected requests. | — | auth | SATISFIED — VERIFIED | Bukti aplikasi terisolasi; production origin terpisah. |
| NFR-008 | SRS:362 | Sistem harus memiliki validasi input. | Input Filament/form/service memvalidasi shape/type/bytes/URL. | D04/D14 | doc | SATISFIED — PARTIALLY VERIFIED | Corpus format nyata dan seluruh UI input belum lengkap. |
| NFR-009 | SRS:364 | Sistem harus memiliki audit log. | Event auth/Page/Menu/Media, password dan export outcome tercatat pada boundary aplikasi. | D08 | audit | SATISFIED — VERIFIED | F30 pasca-P9 teruji pada outcome representatif; retensi/manual observabilitas produksi tetap gate D08. |
| NFR-010 | SRS:366 | Sistem harus mendukung backup database. | pg_dump/pg_restore teknis tersedia dan rehearsal subset lulus. | D09 | ops | SATISFIED — PARTIALLY VERIFIED | Jadwal/kebijakan dan recovery unit lengkap belum. |
| NFR-011 | SRS:368 | Website harus dapat berjalan pada hosting shared. | Shared hosting hanya mungkin bila menyediakan seluruh runtime/worker/PostgreSQL/binary. | Pemilik/deployment target | ops | OWNER DECISION PENDING | Tidak ada target shared-host yang dibuktikan; jangan menyebut VPS sebagai persetujuan penggantian requirement. |
| NFR-012 | SRS:370 | Website harus kompatibel dengan browser modern. | Frontend modern tetap digunakan. | — | ui | IMPLEMENTED — MANUAL VERIFICATION PENDING | Tidak ada matriks browser riil. |
| NFR-013 | SRS:372 | Website harus mendukung SEO dasar. | Title/description/slug dasar tersedia. | — | public | SATISFIED — PARTIALLY VERIFIED | Tidak ada crawl/SEO end-to-end atau audit hasil mesin pencari. |
| NFR-014 | SRS:374 | Website harus memiliki performa yang baik pada jaringan seluler. | Performa seluler memerlukan workload dan target terukur. | Acceptance kapasitas F40 | ui | DEFERRED | Belum benchmark target; deferral tidak berarti owner menerima. |
| NFR-015 | SRS:376 | Website harus menggunakan media optimization. | Kandidat JPEG/PNG/WebP diskalakan tanpa upscale, dikompresi konservatif dan thumbnail 480×270 dibuat privat. | D14 approved | media | SATISFIED — VERIFIED | Full suite 453/453 mencakup fixture nyata PNG/JPEG/WebP, generation privat, thumbnail dan delivery; HEIC runtime pending. |
| BR-001 | SRS:382 | Hanya terdapat satu akun admin. | Satu Admin dijaga supported provisioning concurrent. | — | single | SATISFIED — PARTIALLY VERIFIED | Manual SQL tidak punya singleton DB constraint. |
| BR-002 | SRS:384 | Admin tidak dapat membuat admin baru. | Tidak ada UI multi-admin; provisioning berhenti bila ada Admin. | — | single | SATISFIED — VERIFIED | Batas supported application, bukan DBA. |
| BR-003 | SRS:386 | Admin tidak dapat menghapus akun admin utama. | Model melindungi penghapusan sole Admin. | — | single | SATISFIED — VERIFIED | Bukan constraint anti-DBA. |
| BR-004 | SRS:388 | Guest tidak memerlukan akun. | Guest tidak memiliki kebutuhan akun. | — | public | SATISFIED — VERIFIED | Protected admin routes tetap terpisah. |
| BR-005 | SRS:390 | Konten dapat dipublikasikan atau diarsipkan. | Publish/archive/draft berjalan dengan public scope; jadwal masa depan dipertahankan pada transisi terbit. | D01/D07/D11 | public | SATISFIED — PARTIALLY VERIFIED | F21 form/HTTP teruji terfokus; legacy media URL dan browser tetap gate. |
| BR-006 | SRS:392 | Audit log wajib disimpan. | Audit tersimpan pada event yang terhubung. | D08 | audit | SATISFIED — PARTIALLY VERIFIED | F30 event aplikasi dikoreksi dan teruji; prosedur retensi manual serta bukti penyimpanan produksi masih pending. |
| BR-007 | SRS:394 | Audit log tidak dapat dihapus manual. | Larangan manual mutlak direkonsiliasi dengan kebijakan audit enam bulan/manual oleh operator yang disahkan. | D08 approved sebagian | audit | SUPERSEDED BY APPROVED DECISION | UI/policy masih immutable; prosedur retensi manual aman belum disahkan/diuji; bukan izin SQL sembarang. |
| BR-008 | SRS:396 | Seluruh konten dikelola melalui dashboard. | Resource modul bisnis tetap tersedia. | — | builder | SATISFIED — PARTIALLY VERIFIED | F22 save atomik teruji pada Page editor aktif; browser workflow lintas modul belum lengkap. |
| BR-009 | SRS:398 | Website harus tetap dapat berjalan meskipun belum memiliki konten. | Empty-state request publik tidak memerlukan konten fixture. | — | public | SATISFIED — VERIFIED | Tes HTTP routes/navigation kosong lulus; visual empty state belum. |
| BR-010 | SRS:400 | Sistem harus dapat digunakan ulang untuk desa lain tanpa perubahan source code utama. | Setting/config/installation ID mendukung satu desa per instalasi. | — | settings | SATISFIED — PARTIALLY VERIFIED | Reprovision desa berbeda/domain/TLS tanpa source changes belum rehearsal. |
| FR-035 | Watermark:482 | Sistem wajib menerapkan watermark tak terlihat pada seluruh media | Invisible wajib pada derivative gambar; PDF/Office Document dan original admin mengikuti keputusan baru. | D03/D04/D05/D06 | watermark | SUPERSEDED BY APPROVED DECISION | Tidak mengklaim watermark dokumen/semua originals. |
| FR-036 | Watermark:483 | Watermark tak terlihat tidak dapat dinonaktifkan oleh admin | Tidak ada toggle invisible; visible toggle terpisah. | D05 | watermark | SATISFIED — VERIFIED | Berlaku pipeline gambar yang didukung. |
| FR-037 | Watermark:484 | Sistem wajib membuat identifier watermark unik untuk setiap media | Metadata signed mengikat installation_id dan media_id unik. | D05 | watermark | SATISFIED — VERIFIED | Tidak mengklaim UUID watermark/perceptual ID yang tak ada. |
| FR-038 | Watermark:485 | Sistem wajib menerapkan watermark tak terlihat pada seluruh derivative | PUBLIC image derivative yang dibuat pipeline ditandatangani. | D03/D05 | watermark | SATISFIED — PARTIALLY VERIFIED | Generasi/format legacy dan key history pending. |
| FR-039 | Watermark:486 | Sistem wajib memverifikasi watermark sebelum media tersedia | Candidate image harus authentic+checksum+format sebelum aktif. | D05/D07 | watermark | SATISFIED — PARTIALLY VERIFIED | Legacy no-checksum dan static origin gate. |
| FR-040 | Watermark:487 | Sistem harus menghasilkan checksum untuk setiap file | Original checksum dan final derivative checksum pada new processing; Document checksum terpisah. | D05 | watermark | SATISFIED — PARTIALLY VERIFIED | Tidak ada backfill checksum legacy. |
| FR-041 | Watermark:488 | Sistem harus menghasilkan fingerprint media apabila format mendukung | Fingerprint berupa SHA-256 server yang disepakati, bukan perceptual hash. | D05 | watermark | SATISFIED — VERIFIED | Tidak tahan transformasi sebarang. |
| FR-042 | Watermark:489 | Admin dapat menjalankan verifikasi watermark | Manual verification membaca file aktual dan menyimpan hasil. | D05 | manual | SATISFIED — VERIFIED | Legacy unverifiable tidak dipromosikan. |
| FR-043 | Watermark:490 | Admin dapat melihat hasil verifikasi | Notification true/false dan metadata/integrity/format outcome teruji Livewire. | D05 | manual | IMPLEMENTED — MANUAL VERIFICATION PENDING | Tampilan browser status/history belum. |
| FR-044 | Watermark:491 | Sistem harus menyimpan metadata provenance | Payload provenance media/installation/type/version/time signed dan log. | D05 | watermark | SATISFIED — PARTIALLY VERIFIED | Tidak ada klaim provenance tahan transformasi/histori key otomatis. |
| FR-045 | Watermark:492 | Media dengan watermark gagal tidak dapat dipublikasikan | New failed candidate tidak aktif; failed manual verification mencabut approval saat berlaku. | D05/D07 | watermark | SATISFIED — PARTIALLY VERIFIED | Legacy compatibility/checksum dan cutover origin pending. |
| FR-046 | Watermark:493 | File yang diunduh melalui dashboard tetap memiliki watermark tak terlihat | Derivative image terlindungi; original boleh admin terotorisasi dan dokumen kontrak sendiri. | D06/D03/D04 | media | SUPERSEDED BY APPROVED DECISION | Bukan semua download admin ber-watermark; keputusan mengubah requirement lama. |
| FR-047 | Watermark:494 | Sistem harus mencatat kegagalan pemrosesan watermark | Verification log dan failure status/job log tersedia. | D05 | watermark | SATISFIED — PARTIALLY VERIFIED | Fatal process/storage/log backend produksi belum diuji. |
| FR-048 | Watermark:495 | Sistem dapat membuat ulang derivative tanpa menghilangkan watermark tak terlihat | Reprocess mengganti candidate verified; previous active tetap saat gagal. | D05 | media | SATISFIED — PARTIALLY VERIFIED | HEIC dan semua crash window/storage nyata pending. |
| NFR-026 | Watermark:503 | Watermark tak terlihat tidak boleh mengganggu tampilan normal media | Metadata invisible bukan overlay visual. | D05 | watermark | IMPLEMENTED — MANUAL VERIFICATION PENDING | Parser/byte fixture teruji, visual format corpus belum. |
| NFR-027 | Watermark:504 | Watermark harus dirancang tahan terhadap kompresi ringan | Tidak ada janji verifikasi setelah kompresi eksternal. | D05 approved A | watermark | SUPERSEDED BY APPROVED DECISION | Transform-resilience sengaja di luar kontrak approved. |
| NFR-028 | Watermark:505 | Watermark harus dirancang tahan terhadap perubahan ukuran wajar | Tidak ada janji tahan resize/crop. | D05 approved A | watermark | SUPERSEDED BY APPROVED DECISION | Transform-resilience bukan bukti yang diklaim. |
| NFR-029 | Watermark:506 | Watermark harus tetap dapat diverifikasi setelah optimasi sistem | Kandidat dioptimasi sebelum watermark/signature, checksum byte final dan verifikasi format; kedua generasi lolos sebelum pointer aktif berubah. | D14/D05 approved | media | SATISFIED — VERIFIED | Full suite 453/453 mencakup byte/checksum final, kegagalan reprocess dan delivery; HEIC runtime pending. |
| NFR-030 | Watermark:507 | Sistem tidak boleh memublikasikan media sebelum verifikasi berhasil | Gambar baru harus verified; dokumen validasi sendiri. | D03/D04/D05/D07 | media | SATISFIED — PARTIALLY VERIFIED | Legacy static bypass masih gate. |
| NFR-031 | Watermark:508 | Pemrosesan media harus dilakukan melalui queue | Caller Media tetap `dispatchSync`; ekspor CSV/XLSX queue adalah alur berbeda. | OWNER-ACCEPTED DEFERRED | media | DEFERRED — SUPERSEDED FOR CURRENT DEPLOYMENT | Jangan klaim Media background; tinjau ulang hanya bila bukti skala/UX meminta async. |
| NFR-032 | Watermark:509 | Kegagalan watermark tidak boleh menghapus file sumber | Failure candidate tidak menghapus immutable original. | D06 | media | SATISFIED — PARTIALLY VERIFIED | PNG failure teruji; HEIC decoder runtime belum. |
| NFR-033 | Watermark:510 | Sistem harus mendukung perubahan versi algoritma watermark | Verifier masih versi 1.0 dan trusted signing key saat ini; tidak ada dispatch/key-history multi-versi. | OWNER-ACCEPTED DEFERRED | watermark | DEFERRED — OWNER ACCEPTED | Perubahan versi/rotasi memerlukan desain kompatibilitas tersendiri; file lama tidak otomatis terverifikasi dengan kunci baru. |
| NFR-034 | Watermark:511 | Identifier instalasi tidak boleh bergantung pada domain | Installation ID tidak berasal APP_URL/domain. | — | watermark | SATISFIED — VERIFIED | Migration domain riil belum diuji. |
| NFR-035 | Watermark:512 | Informasi rahasia algoritma atau signing key tidak boleh tersimpan pada repository | Kunci dari config/env; output/dokumen P9 tidak memuat nilai rahasia. | — | watermark | SATISFIED — PARTIALLY VERIFIED | Custody/histori Git/Guidebook08 content audit bukan cakupan; hanya structural. |
| NFR-036 | Watermark:513 | Sistem harus menyediakan audit trail pemrosesan media | WatermarkVerificationLog dan status job/log tersedia. | D08 | watermark | SATISFIED — PARTIALLY VERIFIED | Lifecycle audit aplikasi diperluas pasca-F30; production log/disk/retention tetap gate. |
| NFR-037 | Watermark:514 | Watermark wajib diterapkan secara konsisten pada seluruh tipe media yang didukung | Image pipeline memenuhi policy gambar; PDF/Office tidak diwajibkan watermark image. | D03/D04/D05 | watermark | SUPERSEDED BY APPROVED DECISION | HEIC/corpus tambahan tetap pending. |
| BR-021 | Watermark:522 | Semua media wajib memiliki watermark tak terlihat | Pengecualian original admin dan Document mengikuti approved decisions. | D03/D04/D06 | watermark | SUPERSEDED BY APPROVED DECISION | Tidak ada blanket watermark seluruh file. |
| BR-022 | Watermark:523 | Tidak ada pengecualian watermark tak terlihat | Pengecualian eksplisit menggantikan larangan pengecualian lama. | D03/D04/D06 | watermark | SUPERSEDED BY APPROVED DECISION | Jangan klaim PDF watermark. |
| BR-023 | Watermark:524 | Admin tidak dapat menonaktifkan watermark tak terlihat | Admin hanya dapat toggle visible watermark. | D05 | watermark | SATISFIED — VERIFIED | Invisible gate tetap. |
| BR-024 | Watermark:525 | Watermark terlihat tidak menggantikan watermark tak terlihat | Visible dilakukan sebelum metadata invisible; kedua pemeriksaan berbeda. | D05 | watermark | SATISFIED — VERIFIED | Logo invalid tidak diam-diam sukses. |
| BR-025 | Watermark:526 | Semua derivative wajib memiliki watermark tak terlihat | Derivatives gambar pipeline diproses/verified. | D03/D05 | watermark | SATISFIED — PARTIALLY VERIFIED | Legacy rows tidak diregenerasi massal. |
| BR-026 | Watermark:527 | Media gagal watermark tidak boleh dipublikasikan | Failed new image candidate privat/tidak aktif. | D05/D07 | media | SATISFIED — PARTIALLY VERIFIED | Known legacy static URL masih origin gate. |
| BR-027 | Watermark:528 | Pengunduhan media tetap menggunakan file terlindungi | Guest derivative/image atau valid Document; admin original controlled exception. | D06/D03/D04 | media | SUPERSEDED BY APPROVED DECISION | D07 legacy origin pending. |
| BR-028 | Watermark:529 | Identifier instalasi tetap sama setelah migrasi domain | INSTALLATION_ID dipertahankan saat domain berubah, runbook eksplisit. | — | watermark | IMPLEMENTED — MANUAL VERIFICATION PENDING | Actual domain migration/restore identity belum. |
| BR-029 | Watermark:530 | Setiap media memiliki identifier watermark berbeda | Payload binds distinct Media ID/installation. | D05 | watermark | SATISFIED — VERIFIED | Generation checksum terpisah trusted DB. |
| BR-030 | Watermark:531 | File mentah tanpa watermark tidak tersedia melalui dashboard | Admin authorized dapat akses original via controlled endpoint untuk edit. | D06 approved A | media | SUPERSEDED BY APPROVED DECISION | Guest dilarang; origin legacy cutover tetap. |
| BR-031 | Watermark:532 | Perubahan algoritma tidak mengubah identitas media | Media identity terpisah dari version field. | D05 | watermark | SATISFIED — PARTIALLY VERIFIED | Perubahan algorithm version nyata belum didukung/diuji (NFR-033). |
| BR-032 | Watermark:533 | Riwayat watermark dan verifikasi harus dapat diaudit | Riwayat verification disimpan di WatermarkVerificationLog. | D08 | manual | SATISFIED — PARTIALLY VERIFIED | Full history UI/retention/cakupan audit belum lengkap. |

## Arahan revisi tanpa ID baru

[Revisi_Dokumen_Village_Website_CMS.txt](Revisi_Dokumen_Village_Website_CMS.txt) mempunyai 11 arahan bernomor bagian. Locator bagian berikut bukan requirement ID tambahan dan tidak menambah total 85.

| Locator sumber | Arahan | Interpretasi / implementasi / bukti / dokumentasi | Status / gate |
|---|---|---|---|
| Revisi §1 | Hapus recovery-code table | Schema tidak memiliki flow recovery codes; D02 memakai command operator terkonfirmasi, tanpa bypass tamu atau reset password. | Prosedur CLI dan regresi disposable lulus; akses operator/walkthrough target belum dibuktikan. |
| Revisi §2 | Hapus remember_token/Remember Me | Model Admin dan login schema harus dibaca bersama perilaku vendor; scope auth teruji, tanpa inventing recovery flow | Static implementation; browser login dan schema/data history perlu review bila kontrak remember berubah. |
| Revisi §3 | Email admin opsional | Login username/password/TOTP; email bukan username login. D10 memilih inbox kontak tanpa notifikasi email. | D10 disahkan; D15 reauth perubahan email teruji terfokus. |
| Revisi §4 | Tanpa draft/scheduled/review | D01 mempertahankan draf dan F21/D11 mendukung penjadwalan; keputusan pemilik terbaru menggantikan arahan revisi lama ini. | Jadwal form/HTTP teruji terfokus; jangan mass-publish/normalize. |
| Revisi §5 | SEO sederhana | Page SEO title/description dan layout defaults, grup public/settings | NFR-013 partial; tidak memperkenalkan SEO framework. |
| Revisi §6 | Menu/submenu/order sederhana | MenuResource/NavigationResolver, P4ANavigationTest/P4BPreviewIntegrationTest | HTTP/Livewire verified; browser pending. |
| Revisi §7 | PDF watermark ditunda | D03 approved B; job image tidak memodifikasi PDF; DocumentFilePolicy/delivery | Unsafe injector removed; PDF Document tetap requirement D04. |
| Revisi §8 | Search judul Page/News/Document | SearchController, PublicSearchTest, P2DataInvariantTest | Verified narrow application contract; F31/F32. |
| Revisi §9 | Peta kategori/titik/koordinat | MapController, Location, PublicMapTest | HTTP eligibility verified; keyboard/browser F41 pending. |
| Revisi §10 | Audit login/logout/gagal/password/content/settings | Auditable/AuditLogService, password/export outcome dan PostP9AuditSettingsTest; group audit | F30 dikoreksi pada event aplikasi; D08 retensi dan observabilitas produksi belum teruji. |
| Revisi §11 | Delapan template Page | PageTemplateService, PageBuilderServiceTest::test_template_service_has_all_approved_templates, PostP9PageBoundaryTest | Template dan atomisitas Page editor teruji di DB disposable. |

PRD modul authentication/dashboard/settings/menu/builder/news/gallery/document/media/contact/map/audit ditautkan ke grup yang sama; UI System Design/Theme/Wireframe merupakan bukti intent, bukan bukti browser. D15 dan kebijakan backup D09 kini disetujui; pelaksanaan backup target tetap menunggu bukti. Tidak ada penomoran buatan untuk klaim naratif tambahan.

## Snapshot gap pasca-P9 sebelum paket keputusan pemilik

Daftar berikut mempertahankan alasan historis paket ini dibuka. Status **terkini** FR-021, NFR-015/NFR-029, F21, D15 dan penundaan NFR-031/NFR-033 terdapat pada baris matriks serta adendum di bagian atas; daftar lama bukan inventaris defect aktif. Full suite paket setelah koreksi test belum diulang.

- F22/F34: koreksi pasca-P9 mengaktifkan transaksi Filament dan menguji child failure melalui CreatePage/EditPage aktual tanpa transaksi dari harness; final full suite 441/441 lulus.
- F24: social_twitter/social_youtube kini memiliki consumer footer dan preview yang teruji; notification_email tetap menunggu D10.
- FR-021/D14: penanda featured Page/Gallery belum consumer publik; News mempunyai consumer.
- F30/NFR-009/BR-006: event aplikasi yang dikonfirmasi telah dihubungkan dan diuji; retensi manual serta observabilitas produksi tetap terpisah.
- NFR-015/NFR-029: optimization/resize/thumbnail belum pipeline aktif. D14 tidak mengizinkan menghapus requirement.
- NFR-033: versi payload literal 1.0 dan satu key bukan dukungan perubahan algoritma/backward verification.
- NFR-031: dispatchSync menjalankan job pada request; background processing bukan bukti yang dapat disimpulkan dari ShouldQueue.

Item di atas tidak diselesaikan melalui perubahan status dokumen. Perbaikan memerlukan pembukaan fase pemilik yang sempit atau acceptance eksplisit bila hendak ditunda. P9 tidak memberi acceptance atas nama pemilik.
