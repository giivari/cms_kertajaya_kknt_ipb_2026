# Inventaris dan kedudukan dokumen

> **Pembaruan D02 — 26 September 2026:** [runbook pemulihan](operations/RECOVERY.md) kini memuat command operator `admin:recover-mfa` yang diuji pada lingkungan disposable; Guidebook 01/07 menunjuk prosedur yang sama. Guarded full suite terbaru **459/459, 2.924 assertion, 573,83 detik** menggantikan baseline 453/453 di snapshot bawah. Prosedur aplikasi/CLI tersedia, tetapi akses operator pada target serta gerbang rilis eksternal lainnya belum diverifikasi. **RELEASE VERIFICATION PENDING.**

> **Status gerbang rilis 26 September 2026:** verifikasi cache sementara dan inspeksi prasyarat tercatat dalam [laporan closure](FINAL_REMEDIATION_CLOSURE_2026-09-25.md). Bukti browser, build riil, DOC/XLS/HEIC, D07 origin, operasi target, D09 backup, dan pemulihan lengkap belum tersedia; runbook tetap merupakan instruksi, bukan bukti pelaksanaan. Baseline suite 453/453 tidak berubah; status rilis tetap **VERIFICATION PENDING**.

> **Pembaruan baseline 26 September 2026:** guarded full suite terakhir lulus **453/453, 2.881 assertion, 192,93 detik** setelah semua koreksi paket keputusan pemilik. Dokumen closure, traceability, dan state di bawah memakai hasil tersebut sebagai baseline otomatis terbaru. Gate browser, origin legacy, build terisolasi, runtime format, serta operasi target tetap bukan bukti suite.

> Pembaruan bukti paket 26 September 2026: source keputusan pemilik telah diterapkan dan tes terfokus terkait lulus. Full suite paket terakhir **443 lulus/8 gagal** sebelum koreksi enam file test historis; keenam file kemudian lulus terfokus, tetapi full suite pascakoreksi belum diulang. Gunakan [adendum closure](FINAL_REMEDIATION_CLOSURE_2026-09-25.md) untuk status terkini; jangan memakai baseline 441/441 pra-paket sebagai bukti suite terbaru hijau.

> Pembaruan keputusan 26 September 2026: [kontrak pemilik](OWNER_APPROVED_DECISIONS_2026-09-26.md) menggantikan label "owner decision pending" D01/D02/D08/D09/D10/D12/D15 pada snapshot P8/P9 di bawah. Dokumen lama tetap disimpan untuk traceability. Kebijakan backup sudah disahkan tetapi operasi backup/restore target belum terverifikasi; D07 origin, browser, format DOC/XLS/HEIC, dan gate deployment tetap terbuka.

Per 25 September 2026. **Instruksi operasi aktif**: `DEPLOYMENT_GUIDE.md`, `operations/RELEASE_VERIFICATION_CHECKLIST.md`, `operations/RUNTIME_PREREQUISITES.md`, `operations/QUEUE_AND_SCHEDULER.md`, `operations/RECOVERY.md`, `operations/EXPORTS.md`, `operations/PREVIEW.md`, `operations/MEDIA_LEGACY_CUTOVER.md`, `LOCAL_DEVELOPMENT.md`, dan `DEVELOPER_SAFETY.md`. `PROJECT_STATE.md` membedakan bukti dan gate. Dokumen requirement/desain lama di bawah tetap disimpan untuk jejak, tetapi klaim implementasi, schema, retensi dan command di dalamnya **bukan instruksi operasi** jika berbeda dengan source/runbook kanonis. Audit historis tetap beku.

| Dokumen | Disposisi P8/P9 | Kedudukan / tindak lanjut |
|---|---|---|
| `README.md` | REWRITE SECTION(S) | Gerbang ke status dan runbook aman. |
| `PROJECT_STATE.md` | REWRITE SECTION(S) | Status kanonis P0–P9; empat finding dibuka kembali, bukan klaim release ready. |
| `FINAL_REMEDIATION_CLOSURE_2026-09-25.md` | ADD P9 | Matriks F01–F46, hasil 434 tes, DoD20, owner decisions, dan material defects. |
| `REQUIREMENT_TRACEABILITY.md` | ADD P9 | 85 ID requirement asli dan locator Revisi; implementation/test/docs/gate direkonsiliasi tanpa mengarang ID. |
| `DOKUMEN INISIASI PROYEK.txt` | OWNER REVIEW REQUIRED | Requirement historis; email kontak D10 belum disahkan. |
| `Revisi_Dokumen_Village_Website_CMS.txt` | KEEP | Riwayat perubahan requirement; tidak dipakai sebagai prosedur recovery. |
| `Product Requirement Document.txt` | OWNER REVIEW REQUIRED | Kebutuhan asli; rekonsiliasi P9 ada di REQUIREMENT_TRACEABILITY, gap dan owner acceptance tetap terbuka. |
| `Product Backlog.txt` | KEEP + ADJUST | Riwayat fase/remediasi; entri lama tidak menggantikan status kanonis. |
| `ROADMAP_UI_DAN_DEPLOYMENT.md` | OWNER REVIEW REQUIRED | Snapshot roadmap lama; klaim deployment/rotasi perlu bukti baru. |
| `Software Architechture.txt` | OWNER REVIEW REQUIRED | Rancangan lama; email queue dan storage tidak boleh dibaca sebagai source aktif. |
| `SECURITY SPECIFICATION DOCUMENT.txt` | OWNER REVIEW REQUIRED | Target keamanan; bedakan dari kontrol yang telah diuji. |
| `Watermark.txt` | OWNER REVIEW REQUIRED | Desain watermark lama; image authenticity/checksum aktif, PDF watermark ditunda. |
| `Entity Relation Diagram.txt` | OWNER REVIEW REQUIRED | Diagram historis termasuk recovery codes yang tidak ada pada schema kini. |
| `Document Data Dictionary.txt` | OWNER REVIEW REQUIRED | Kamus historis; schema kanonis adalah migrasi/model saat ini. |
| `Activity Diagram.txt` | OWNER REVIEW REQUIRED | Alur email/retensi lama belum sesuai implementasi. |
| `User Flow.txt` | KEEP + ADJUST | Journey historis; aktual UI/preview mengacu source dan status. |
| `Use Case Diagram.txt` | OWNER REVIEW REQUIRED | Email kontak masih keputusan D10. |
| `sitemap.txt` | KEEP + ADJUST | Cache sementara P9 memuat route aktif; cache normal masih ListMenus dan perlu regenerasi terkontrol pada target. |
| `UI System Design Core.txt` | KEEP | Bahasa visual dipertahankan; verifikasi browser P7 pending. |
| `Theme Specification Document for kertajaya.txt` | KEEP | Tema historis tidak diubah tanpa bukti browser/desain. |
| `Wireframe.txt` | KEEP + ADJUST | Ilustrasi, bukan bukti perilaku browser. |
| `LOCAL_DEVELOPMENT.md` | REWRITE SECTION(S) | Panduan lokal aman; nama database/cluster dari lingkungan lama bukan target umum. |
| `DEVELOPER_SAFETY.md` | REWRITE SECTION(S) | Instruksi guardrail yang dapat diikuti; hapus residu generator. |
| `Deployment Specification.txt` | ARCHIVE AFTER MERGE | Nilai backup/RPO/TLS lama belum disetujui; kontrak operasi dipindah ke runbook P8. File tetap ada untuk traceability. |
| `DEPLOYMENT_GUIDE.md` | REWRITE SECTION(S) | Fresh install/update/rollback/recovery terpisah; instruksi drop/ekspos DB lama dicabut. |
| Guidebook `01`–`06` | KEEP + ADJUST | Bahasa Admin disesuaikan dengan perilaku nyata; tidak menjadi runbook rahasia. |
| Guidebook `07` | SPLIT | Ringkasan maintenance saja; rujuk deployment/recovery/worker kanonis. |
| Guidebook `08_DOKUMEN_SERAH_TERIMA_RAHASIA.md` | OWNER REVIEW REQUIRED | Hanya metadata/custody ditinjau di P8; isi rahasia tidak dibaca/disalin. |
| `resources/fonts/tcpdf/README.md` | KEEP | Descriptor font; tidak diubah. |
| `operations/MEDIA_LEGACY_CUTOVER.md` | KEEP + ADJUST | Gate origin D07 tetap berlaku; rollback tidak membuka URL lama; inventory memerlukan scope storage jelas. |
| `operations/RELEASE_VERIFICATION_CHECKLIST.md` | KEEP | Checklist kanonis release candidate: artifact, migrasi, build/cache, D07/D09/D02, recovery, browser/format, stop dan rollback. |
| `operations/RUNTIME_PREREQUISITES.md` | KEEP | Matriks prasyarat source dan gate runtime. |
| `operations/QUEUE_AND_SCHEDULER.md` | KEEP | Worker/scheduler aktif dan observabilitas. |
| `operations/EXPORTS.md` | KEEP | Kontrak P5, termasuk D12 non-snapshot. |
| `operations/PREVIEW.md` | KEEP | Kontrak P4 tanpa autosave. |
| `operations/RECOVERY.md` | KEEP | Unit restore dan bukti rehearsal disposable. |
| `AGENTS.md` | KEEP | Kontrak agent lokal; tidak ditulis ulang. |
| `FULL_REPOSITORY_AUDIT_2026-09-17.md` | KEEP | Baseline historis beku di parent workspace. |

Tidak ada dokumen historis yang dihapus atau dipindah dalam P8/P9. Kategori `OWNER REVIEW REQUIRED` bukan persetujuan requirement lama dan bukan alasan mengubah perilaku aplikasi. P9 telah merekonsiliasi traceability dan closure; **material defects serta gate operasional/browser tetap terbuka**. Koreksi dokumentasi P9 yang terbatas: media memakai dispatchSync pada caller aktif (bukan otomatis worker), dan social Twitter/YouTube belum dikonsumsi footer publik. Audit historis dan isi rahasia Guidebook08 tidak diubah.

**Adendum pasca-P9, 26 September 2026:** status F22/F24/F30/F34 serta hasil full-suite terbaru berada di [adendum laporan closure](FINAL_REMEDIATION_CLOSURE_2026-09-25.md), [traceability](REQUIREMENT_TRACEABILITY.md), dan [PROJECT_STATE](PROJECT_STATE.md). Pernyataan P9 pada paragraf sebelumnya adalah snapshot historis: social Twitter/YouTube sekarang dikonsumsi footer dan preview dengan URL aman. Full suite final guarded setelah assertion audit ekspor dikoreksi lulus **441/441 dengan 2.782 assertion dalam 81,83 detik**. Gate origin/browser/operasional dan gap requirement lain tetap terbuka. Audit historis dan Guidebook08 tetap tidak diubah.
