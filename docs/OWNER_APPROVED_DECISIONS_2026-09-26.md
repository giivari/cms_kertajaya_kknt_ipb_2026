# Keputusan pemilik dan batas implementasi — 26 September 2026

Dokumen ini mencatat keputusan terbaru. Audit 17 September dan matriks P9 tetap riwayat; status terbaru harus dibaca bersama hasil tes pada `PROJECT_STATE.md`. Keputusan kebijakan tidak membuktikan operasi pada server target.

| Keputusan | Kontrak yang disetujui | Batas implementasi / verifikasi |
|---|---|---|
| D01 | Draf tetap tersedia untuk Admin dan tidak publik. | Tidak ada publikasi massal. |
| D02 | Pemulihan MFA hanya melalui operator berwenang; tidak ada recovery code atau endpoint tamu. | Kasus kehilangan akses memerlukan verifikasi identitas/otorisasi terpisah, pencatatan tindakan, checkpoint, dan uji pada salinan terisolasi. Mekanisme reset operator yang dapat dijalankan masih harus disiapkan/dibuktikan sebelum diklaim operasional; tidak boleh mengedit database produksi secara bebas. |
| D08 | Retensi audit enam bulan dengan penghapusan manual berwenang hanya untuk audit log. | Tidak ada auto-purge Media, pesan kontak, atau data CMS lain. |
| D09 | RPO maksimum 24 jam; RTO target 4 jam; backup terenkripsi off-host harian; 30 titik harian dan 12 titik bulanan; latihan pemulihan sedikitnya triwulanan. | Backup satu unit meliputi PostgreSQL, original, derivative yang diperlukan, dokumen/file privat, APP_KEY, kunci watermark beserta riwayat yang diperlukan, INSTALLATION_ID, konfigurasi, metadata release/lock/build, domain/TLS, serta definisi worker/scheduler. Infrastruktur dan pemulihan produksi **belum diverifikasi**. |
| D10 | Pesan kontak masuk inbox Admin saja. | Notifikasi email ditunda. `notification_email` belum menjadi konsumen pengiriman dan tidak boleh disebut aktif. |
| D12 | Ekspor memakai identitas permintaan/filter/urutan/chunk yang stabil; nilai antar-chunk boleh berubah. | Tidak ada jaminan snapshot nilai pada satu waktu. |
| D14 | Kontrol produk yang sah harus memiliki efek nyata. | Featured Page/Gallery dan optimasi gambar termasuk cakupan implementasi sekarang; setting social tetap dikonsumsi footer/preview. |
| D15 | Sandi baru minimal 12 karakter, huruf besar, huruf kecil, angka; simbol disarankan. Hash baru Argon2id; hash lama tetap dapat diverifikasi. | Kegagalan login bertahap 1/3/5/10 detik, kegagalan kelima memulai lockout 15 menit pada pasangan identitas-klien; login berhasil menghapus state. Perubahan sandi/email/username memerlukan sandi saat ini dan TOTP aktif pada setiap simpan. Kebijakan MFA wajib tetap berlaku. |
| F21 / D11 | Status `published` dengan `published_at <= now()` menentukan ketersediaan publik. | Waktu masa depan yang dimasukkan Admin dipertahankan saat status menjadi `published`; waktu kosong diisi saat terbit segera. Data lama tidak dinormalisasi massal. |
| FR-021 | Featured Page/Gallery memiliki konsumen beranda yang dibatasi dan mengikuti D11. | Gallery featured diprioritaskan di kisi galeri, Page featured tampil dalam bagian kecil tersendiri. |
| NFR-015 / NFR-029 | Kandidat gambar privat dioptimasi, thumbnail privat dibuat, lalu watermark/signature/checksum/format diverifikasi pada byte final sebelum aktivasi. | JPEG/PNG/WebP dicakup; HEIC memerlukan decoder dan verifikasi runtime terpisah. Original tetap privat dan utuh. Tidak ada klaim tahan crop/resize/kompresi eksternal. |
| NFR-031 | Pemrosesan Media sinkron saat ini diterima pemilik. | Queue `ShouldQueue` tidak berarti job Media berjalan background; ekspor CSV/XLSX memiliki queue tersendiri. |
| NFR-033 | Verifikasi algoritma versi 1.0 dengan trusted key saat ini diterima pemilik. | Perubahan versi dan rotasi/key-history kompatibel ditunda secara eksplisit; file lama tidak dijanjikan terverifikasi dengan kunci/algoritma masa depan. |

Aturan D07 tetap merupakan gate eksternal: route media terkontrol tidak menutup akses langsung pada file legacy di web server sampai cutover origin `/storage/media/**` dan `/storage/originals/**` diterapkan serta diuji. Keputusan ini tidak mengubah kebutuhan browser, DOC/XLS, HEIC, build target, worker/scheduler, TLS/proxy, dan latihan pemulihan lengkap sebelum persetujuan rilis.
