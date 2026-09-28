# 6. Pemecahan masalah untuk Admin

**Tidak bisa masuk atau MFA gagal?** Periksa username/sandi, waktu perangkat autentikator, dan status layanan. Jangan mengulang kode/menonaktifkan MFA dengan mengedit database. Hubungi operator berwenang; kebijakan recovery MFA final D02 belum diputuskan. Satu akun Admin tidak berarti sesi hanya boleh pada satu perangkat.

**Lupa sandi?** Bila masih masuk, gunakan perubahan sandi di profil dengan verifikasi yang diminta. Bila akses hilang, operator harus mengikuti [panduan recovery](../operations/RECOVERY.md) dan keputusan pemilik; tidak ada prosedur reset database bebas atau recovery code aktif yang boleh diajarkan.

**Gambar tidak muncul?** Lihat status pemrosesan/verifikasi, tunggu worker bila job tertunda, lalu muat ulang. Original privat tidak menjadi URL publik. Gagal reprocess seharusnya mempertahankan derivative lama yang valid. Jika terus gagal, catat ID/status tanpa mengunggah rahasia dan minta operator memeriksa log/job serta kapasitas disk. HEIC membutuhkan decoder runtime yang belum dibuktikan produksi.

**Dokumen tidak bisa diunduh?** Periksa apakah statusnya terbit, `published_at` sudah tiba, dan file PDF/Word/Excel masih ada serta lolos checksum/format. Menyimpan draf atau memakai pratinjau tidak membuat dokumen publik. Jangan mengubah status file langsung menjadi verified.

**Konten tersimpan tetapi belum terlihat publik?** Periksa status terbit dan waktu publikasi. Arsip/draf/future tidak memenuhi predicate publik. Menu tujuan juga menyembunyikan konten tidak layak publik; Header dan Footer memiliki menu terpisah.

**Pratinjau hilang?** Token hanya berlaku untuk Admin dan sesi pemilik selama TTL saat ini 30 menit. Pratinjau tidak menyimpan konten bisnis. Simpan perubahan melalui tindakan editor yang sah; untuk detail teknis lihat [PREVIEW](../operations/PREVIEW.md).

**Ekspor menunggu atau gagal?** CSV/XLSX memerlukan worker queue; PDF dibuat sinkron. Periksa notifikasi/status, lalu operator meninjau queue/failed jobs dan penyimpanan privat. Jangan menghapus berkas ekspor manual atau menganggap status completed sebelum artefak diverifikasi.

**URL navigasi 404?** Pastikan target internal masih diterbitkan dan waktunya tiba; URL kustom harus aman (`https://` atau jalur lokal yang benar). Dokumen memakai tautan Document terkelola, bukan URL original media. Minta operator memeriksa route cache target bila route lama masih menunjuk `ListMenus`.

**Form kontak gagal?** Periksa validasi, rate limit, Turnstile dan hostname/secret oleh operator. D10 menyetujui pesan masuk inbox Admin saja; notifikasi email otomatis ditunda.

**Masalah server, backup, atau kunci?** Jangan jalankan reset database, hapus storage, atau putar kunci secara spontan. Gunakan [panduan teknis](07_PANDUAN_TEKNIS_DAN_MAINTENANCE.md) dan [RECOVERY](../operations/RECOVERY.md).
