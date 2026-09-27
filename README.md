# Village CMS Desa Kertajaya

CMS satu desa/satu Admin berbasis Laravel 12, PostgreSQL, Filament 4, Livewire, Blade, dan Tailwind. Modul aktif mencakup halaman, berita, galeri, lokasi, dokumen terkelola (PDF, Word, Excel), pesan kontak, media, navigasi, preview, dan ekspor Admin.

**Status P9: CLOSURE AUDIT COMPLETE — MATERIAL DEFECTS REMAIN.** Suite P9: **434 lulus, 0 gagal, 2.688 assertion, 91,45 detik** pada PostgreSQL disposable. Pemeriksaan caller aktif menemukan transaksi parent/child Page belum mencakup satu logical save; tes yang lulus memasok transaksi sendiri. F22/F34 dibuka kembali, bersama gap consumer settings F24 dan audit events F30. Baca [laporan closure](docs/FINAL_REMEDIATION_CLOSURE_2026-09-25.md), [traceability requirement](docs/REQUIREMENT_TRACEABILITY.md), dan [status proyek](docs/PROJECT_STATE.md). Rilis belum disetujui; gate browser, origin legacy, format, operasi, dan keputusan pemilik tetap berlaku.

## Memulai dengan aman

- Instalasi baru pada database **kosong**: [deployment — Fresh install](docs/DEPLOYMENT_GUIDE.md).
- Memperbarui instalasi yang sudah berisi data: [deployment — Update](docs/DEPLOYMENT_GUIDE.md).
- Pengembangan lokal: [LOCAL_DEVELOPMENT](docs/LOCAL_DEVELOPMENT.md) dan [DEVELOPER_SAFETY](docs/DEVELOPER_SAFETY.md).
- Pemulihan: [RECOVERY](docs/operations/RECOVERY.md). Database, berkas, kunci, identitas instalasi, dan konfigurasi harus diperlakukan sebagai satu unit.

Jangan memakai `migrate:fresh`, menghapus database/media, atau menjalankan `key:generate` pada update atau recovery. Script Composer `setup` dan `post-create-project-cmd` bukan prosedur update; keduanya memuat inisialisasi yang hanya masuk akal pada instalasi baru.

## Kontrak operasi

[Prasyarat runtime](docs/operations/RUNTIME_PREREQUISITES.md) · [worker dan scheduler](docs/operations/QUEUE_AND_SCHEDULER.md) · [ekspor](docs/operations/EXPORTS.md) · [preview](docs/operations/PREVIEW.md) · [cutover media legacy](docs/operations/MEDIA_LEGACY_CUTOVER.md) · [inventaris dokumen](docs/DOCUMENT_STATUS.md)

Original media privat; derivative publik dan dokumen melalui route terkontrol. Preview bukan autosave atau publikasi. Pesan kontak saat ini masuk ke inbox Admin tanpa email otomatis; keputusan produk D10 masih menunggu pemilik. Watermark PDF ditunda, tanpa mengurangi dukungan dokumen PDF.
