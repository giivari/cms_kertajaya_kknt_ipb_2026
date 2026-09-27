# Status proyek Village CMS

> **Status terkini D02 — 26 September 2026:** command operator `admin:recover-mfa` dan prosedur [RECOVERY](operations/RECOVERY.md) tersedia. Enam tes D02 dan 65 tes auth/audit terfokus lulus pada PostgreSQL disposable; guarded full suite baru **459/459, 2.924 assertion, 573,83 detik** menggantikan baseline 453/453. Password tetap, enrollment TOTP lama dihapus, sesi/versi keamanan lama dicabut, dan enrollment ulang memakai jalur normal. Ketersediaan CLI/walkthrough pada target belum diuji. **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS; RELEASE VERIFICATION PENDING.** Paragraf 453/453 di bawah adalah snapshot sebelum D02.

> **Gerbang rilis 26 September 2026:** guardrail test/build lulus; cache route/config testing sementara berhasil memuat 78 route tanpa `ListMenus`, lalu dihapus. Cache route normal masih stale. Build riil tidak dijalankan karena output wrapper menuju `public/build` kerja. Browser tidak tersedia; DOC/XLS asli, HEIC, cutover origin D07, worker/scheduler/storage/TLS target, backup D09, dan rehearsal recovery lengkap masih menunggu bukti. `schedule:list` hanya menunjukkan dua task hourly. Tidak ada source atau data kerja yang diubah. Baseline 453/453 tetap berlaku; **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS; RELEASE VERIFICATION PENDING**. Lihat [laporan closure](FINAL_REMEDIATION_CLOSURE_2026-09-25.md).

> **Baseline verifikasi final — 26 September 2026:** `safe-test.ps1` menghasilkan **453 lulus, 0 gagal, 2.881 assertion, 192,93 detik** pada PostgreSQL disposable dan storage test terisolasi. Ini menggantikan run paket 443/8 serta baseline 441/441 pra-paket. FR-021, NFR-015/NFR-029, F21, dan D15 memiliki bukti otomatis terkini; NFR-031/NFR-033 tetap penundaan yang disetujui. **IMPLEMENTATION COMPLETE WITH DEFERRED ITEMS; RELEASE VERIFICATION PENDING.**

> **Status paket 26 September 2026:** source featured Page/Gallery, optimasi/thumbnail dan verifikasi final, jadwal publikasi, serta D15 telah diterapkan. Satu full suite guarded paket menghasilkan **443 lulus/8 gagal, 2.792 assertion, 733,32 detik**; delapan kegagalan adalah asersi test historis yang kemudian dikoreksi, dan keenam file terkait lulus **53/53** pada rerun terfokus. Full suite **belum diulang** setelah koreksi test sesuai batas paket. Baseline hijau penuh terakhir 441/441 adalah sebelum paket; release verification tetap pending. NFR-031/NFR-033 ditunda dengan persetujuan pemilik. Lihat [adendum closure terbaru](FINAL_REMEDIATION_CLOSURE_2026-09-25.md).

> **Paket keputusan pemilik 26 September 2026:** D01/D02/D08/D09/D10/D12/D14/D15, F21, FR-021, NFR-015/NFR-029, dan penundaan NFR-031/NFR-033 telah disetujui; lihat [kontrak keputusan](OWNER_APPROVED_DECISIONS_2026-09-26.md). Source featured, jadwal, optimasi gambar/thumbnail, dan kebijakan autentikasi telah ditambahkan. Hasil tes paket serta suite final dicatat dalam adendum closure terbaru setelah verifikasi. Paragraf/tabel P9 di bawah adalah snapshot sebelum paket ini; **release verification tetap pending**.

> **Pembaruan pasca-P9, 26 September 2026:** F22/F24/F30/F34 telah dikoreksi dan lulus tes terfokus; tabel P9 di bawah merekam snapshot sebelum koreksi. Full suite guarded setelah assertion audit ekspor dikoreksi: **441 lulus, 0 gagal, 2.782 assertion, 81,83 detik**. [Adendum closure](FINAL_REMEDIATION_CLOSURE_2026-09-25.md) dan [matriks requirement](REQUIREMENT_TRACEABILITY.md) memuat status terbaru.

Status ini mencatat bukti repository lokal per 25 September 2026. Ini **bukan** persetujuan rilis atau bukti keadaan server produksi. [Audit 17 September](../../FULL_REPOSITORY_AUDIT_2026-09-17.md) adalah baseline historis; [rencana remediasi](../../MASTER_REMEDIATION_PLAN_2026-09-17.md) adalah rencana eksekusi. Klaim lama tentang rilis VPS stabil dan semua fitur selesai tidak berlaku sebagai status saat ini.

| Fase | Implementasi | Bukti yang tersedia | Gate tersisa |
|---|---|---|---|
| P0 | Diimplementasikan | Normalisasi discovery dan guardrail statis | Verifikasi operasional akhir |
| P1A | Diimplementasikan | Boundary keamanan dalam tes terfokus dan suite P7 | Peninjauan produksi; D15 tetap terbuka |
| P2 | Koreksi F22 diterapkan | PostgreSQL disposable; kegagalan child pada CreatePage/EditPage aktif rollback parent/anak tanpa outer transaction test | Cache target dan sisa konkurensi produksi |
| P3-A | Source lifecycle selesai | Delivery dan akses original teruji terisolasi | **Cutover origin `/storage/media/**` dan `/storage/originals/**` belum dilakukan**; HEIC dan cakupan produksi |
| P3-B | Diimplementasikan | Tes terfokus watermark, checksum, format, logo | Key/runtime produksi; watermark PDF ditunda sesuai D03 |
| P3-C | Diimplementasikan | Tes terfokus PDF/DOCX/XLSX | Fixture DOC/XLS nyata, legacy reconciliation, produksi |
| P4 | Diimplementasikan | Navigasi/preview dalam suite penuh | Browser dan parity visual akhir |
| P5 | Diimplementasikan | Artefak ekspor dan lifecycle dalam suite penuh | Worker/scheduler/storage produksi; D12 |
| P6 | Rekonstruksi luas selesai; F34 diperbaiki pasca-P9 | Tes actual Filament create/edit kini membuktikan rollback; final full suite 453/453 lulus | Browser dan gate operasional tetap terpisah |
| P7 | Consumer social F24 diperbaiki pasca-P9 | Footer/preview Twitter/X dan YouTube serta URL safety teruji; query/count/search teruji | **Browser:** loader download, keyboard map, responsif; consumer D14 lain |
| P8 | Dokumentasi dan rehearsal teknis | Lihat [RECOVERY](operations/RECOVERY.md) untuk hasil dan batas rehearsal | Penetapan kebijakan backup serta verifikasi deployment |
| P9 | Closure audit selesai; adendum koreksi pasca-P9 | P9 historis 434/434; full suite pasca-koreksi 441/441 | Gap requirement yang belum diimplementasikan dan gate rilis eksternal |

Status pasca-P9: **IMPLEMENTATION INCOMPLETE — RELEASE VERIFICATION PENDING**. Koreksi transaksi Filament Page create/edit dan audit outcome teruji melalui komponen aktual pada DB disposable tanpa transaksi yang disuplai test. Consumer social footer/preview terhubung dan URL aman. Full suite final 441/441 lulus. Implementasi masih belum meliputi sejumlah requirement featured/optimization/version/background yang tercatat eksplisit. [Laporan closure P9](FINAL_REMEDIATION_CLOSURE_2026-09-25.md) memuat adendum, 20 DoD, dan gate; [requirement traceability](REQUIREMENT_TRACEABILITY.md) tidak menghapus requirement yang belum terpenuhi.

Satu instalasi melayani satu desa dan satu Admin. Konten publik memakai `status = published` dan `published_at <= now`; data lama tidak diubah massal. Media original dan ekspor berada di penyimpanan privat; derivative dan dokumen dilayani melalui route terkontrol. URL statis legacy tetap berisiko sampai [cutover origin](operations/MEDIA_LEGACY_CUTOVER.md) diterapkan dan dibuktikan. Preview bukan simpan maupun publikasi. PDF watermark tidak aktif; dokumen PDF/Word/Excel memakai kontrak dokumen sendiri.

**Keputusan pemilik terbaru:** D01/D02/D08/D09/D10/D12/D14/D15 telah disahkan sesuai [kontrak keputusan](OWNER_APPROVED_DECISIONS_2026-09-26.md). Retensi manual enam bulan hanya berlaku untuk audit log. Kesiapan operasional pemulihan MFA dan backup produksi tetap harus dibuktikan.

**Perilaku kontak saat ini:** form memvalidasi dan menyimpan pesan ke inbox Admin. D10 menyetujui inbox saja; notifikasi email ditunda dan `MAIL_*` bukan prasyarat alur kontak.

Panduan operasi kanonis: [deployment](DEPLOYMENT_GUIDE.md), [prasyarat runtime](operations/RUNTIME_PREREQUISITES.md), [worker/scheduler](operations/QUEUE_AND_SCHEDULER.md), [ekspor](operations/EXPORTS.md), [preview](operations/PREVIEW.md), dan [recovery](operations/RECOVERY.md). [Inventaris dokumen](DOCUMENT_STATUS.md) membedakan dokumen historis dari instruksi aktif.
