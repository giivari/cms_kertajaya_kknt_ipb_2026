# 4. Manajemen Media dan Audit Keamanan

Sistem CMS ini didesain agar sangat ketat mengontrol media (gambar) yang keluar masuk dari *server*. Seluruh gambar bermuara di fitur **Perpustakaan Media**.

## A. Perpustakaan Media (Media Library)
Jangan asal tempel (*upload*) gambar di dalam teks berita! Semua foto dan gambar harus diunggah lewat menu **Perpustakaan Media**. 

Mengapa demikian?
1. Saat Anda mengunggah gambar baru, gambar tidak akan langsung tersedia. Statusnya adalah *Pending* / Menunggu.
2. Sistem memproses dari original privat dalam permintaan unggah/proses ulang saat ini; proses dapat membuat Admin menunggu. Watermark terlihat mengikuti pengaturan yang berlaku, dan hasil baru hanya aktif setelah verifikasi metadata, checksum byte final, dan format gambar. Worker queue diperlukan untuk ekspor yang diantrekan, bukan untuk caller media sinkron saat ini.
   Kandidat JPEG/PNG/WebP diperkecil tanpa upscale menurut target hasil publik (default 1920×1080) dan dikompresi secara konservatif; thumbnail maksimum 480×270 dibuat privat. Optimasi mendahului watermark dan checksum/verifikasi byte final. Batas dimensi unggah `max_image_width`/`max_image_height` tetap berlaku; original tidak ditimpa. HEIC bergantung decoder target dan bukti runtime tersendiri. NFR-031 Media sinkron dan NFR-033 kompatibilitas algoritma masa depan secara eksplisit ditunda oleh pemilik; verifier saat ini memakai versi 1.0 dan trusted key aktif.
3. Status **Verified** berlaku pada hasil yang lolos pemeriksaan; reprocess gagal tidak menggantikan derivative lama yang masih valid.
4. Hanya gambar berstatus *Verified* inilah yang nanti bisa ditarik dan dipakai saat Anda membuat Berita, Halaman, atau mengatur profil Beranda. 


Watermark/checksum membantu autentikasi dan integritas byte di server, tetapi tidak mencegah seseorang menyalin layar, crop, resize, atau kompres ulang gambar. Original privat hanya dapat diakses Admin berwenang. PDF watermark ditunda; dokumen PDF tetap dapat dikelola tanpa klaim watermark gambar.

## B. Log Aktivitas (Audit Log)
Menu **Log Aktivitas** mencatat tindakan yang telah diberi audit oleh aplikasi; cakupan lengkap semua tindakan belum boleh diasumsikan.
- Periksa tindakan yang benar-benar tercatat bila terjadi insiden; jangan menganggap setiap kejadian selalu memiliki entri.
- Jika ada kesalahan atau kelalaian pengelolaan web, perangkat desa bisa melihat riwayat jejak langkah siapa yang bertanggung jawab mengubahnya melalui halaman ini.
- Admin tidak menghapus log melalui UI. Keputusan retensi audit log adalah enam bulan dengan penghapusan manual yang berwenang; belum ada job purge audit otomatis. Kebijakan ini tidak berlaku otomatis bagi pesan kontak, media, konten, atau backup.

## C. Pesan Masuk (Komunikasi)
Warga yang mengisi form di halaman *Contact Us* web publik, pesannya akan tersimpan di menu **Pesan Masuk**.
- Cek kotak ini secara berkala.
- D10 menyetujui inbox Admin saja; notifikasi email otomatis ditunda.
- Di dalamnya terdapat Nama Pengirim, Email, No. HP, dan Isi Keluhan / Saran.
- Website tidak mendukung balas pesan (*chat*) bolak-balik langsung dari dalam. Jika surat dirasa mendesak, admin diharapkan menghubungi warga secara mandiri (via WhatsApp atau Email).
