# 4. Manajemen Media dan Audit Keamanan

Sistem CMS ini didesain agar sangat ketat mengontrol media (gambar) yang keluar masuk dari *server*. Seluruh gambar bermuara di fitur **Perpustakaan Media**.

## A. Perpustakaan Media (Media Library)
Jangan asal tempel (*upload*) gambar di dalam teks berita! Semua foto dan gambar harus diunggah lewat menu **Perpustakaan Media**. 

Mengapa demikian?
1. Saat Anda mengunggah gambar baru, gambar tidak akan langsung tersedia. Statusnya adalah *Pending* / Menunggu.
2. Secara bersamaan (*Real-Time* / Sinkron), sistem akan mengompres ukuran gambar tersebut, membuat variasi ukuran kecil untuk *thumbnail*, dan menempelkan teks "Hak Cipta / Logo" Desa secara semi-transparan (*Watermark*).
3. Gambar yang sudah selesai diproses akan berubah status menjadi **Verified** (Terverifikasi).
4. Hanya gambar berstatus *Verified* inilah yang nanti bisa ditarik dan dipakai saat Anda membuat Berita, Halaman, atau mengatur profil Beranda. 

![Screenshot Perpustakaan Media](/_assets_guide/media_library.png) *[Placeholder: Gambar daftar media dengan status Verified]*

Ini membuat foto-foto karya jurnalistik desa aman dari tindak pencurian pihak tak bertanggung jawab.

## B. Log Aktivitas (Audit Log)
CCTV Digital yang tidak bisa dimatikan!
Menu **Log Aktivitas** merekam semua tindakan admin.
- Kapan pengaturan diubah? Siapa yang membuat berita X? Kapan gambar Y dihapus? Semuanya tercatat permanen.
- Jika ada kesalahan atau kelalaian pengelolaan web, perangkat desa bisa melihat riwayat jejak langkah siapa yang bertanggung jawab mengubahnya melalui halaman ini.
- Sebagai admin, Anda tidak akan pernah bisa menghapus isi tabel *Log Aktivitas* ini. Sistem yang akan melakukan pembersihan otomatis di belakang layar (*Background Job*) setiap jangka waktu tertentu (misal setahun sekali) jika diatur demikian.

## C. Pesan Masuk (Komunikasi)
Warga yang mengisi form di halaman *Contact Us* web publik, pesannya akan tersimpan di menu **Pesan Masuk**.
- Cek kotak ini secara berkala.
- Di dalamnya terdapat Nama Pengirim, Email, No. HP, dan Isi Keluhan / Saran.
- Website tidak mendukung balas pesan (*chat*) bolak-balik langsung dari dalam. Jika surat dirasa mendesak, admin diharapkan menghubungi warga secara mandiri (via WhatsApp atau Email).
