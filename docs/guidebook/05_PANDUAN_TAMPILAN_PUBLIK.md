# 5. Panduan Tampilan Publik (Guest View)

Bagian ini bukan ditujukan untuk mengubah isi sistem, melainkan menjelaskan bagaimana konten yang telah Anda buat di *Panel Admin* dikonversi dan ditampilkan ke pengunjung biasa (Warga Desa atau Tamu dari luar). Memahami alur ini akan membantu Anda mengemas informasi dengan lebih rapi.

## A. Navigasi Website dan Kaki Halaman
- Di halaman publik, **Navigasi** yang Anda atur lewat admin akan muncul di bagian paling atas (*Header*) pada tampilan desktop dan menu mobile.
- Ikon logo kecil dan nama yang di-klik akan selalu membawa pengguna kembali ke Beranda (Halaman Utama).
- Bagian **Kaki Halaman (Footer)** tidak memiliki menu navigasi terpisah. Isinya berasal dari Pengaturan Website, termasuk identitas desa, media sosial, kontak, jam layanan, teks kaki halaman, dan tautan footer yang tersedia.

## B. Halaman Beranda (Home)
Kesan pertama pengunjung ada di sini! Terdiri dari beberapa blok (*section*):
1. **Hero Banner:** Menampilkan Teks Penyambut berukuran besar di atas Gambar Utama (Foto *Landscape* desa yang dipilih di panel admin). Sangat mencolok.
2. **Profil Desa:** Teks singkat berdampingan dengan 2 foto kecil. Sangat cocok diisi dengan ringkasan Visi Misi atau Sejarah Singkat.
3. **Statistik (Angka Bergerak):** Empat kolom khusus menampilkan jumlah penduduk, luasan wilayah, KK, dan Dusun. Angka ini hanya berupa teks biasa yang harus admin perbarui tiap periode, *bukan* ditarik otomatis dari sistem kependudukan (Disdukcapil).
4. **Potensi Desa:** Menampilkan kartu-kartu kecil yang mengarah ke artikel khusus (Misal: Kerajinan, Pertanian).

## C. Kumpulan Daftar Konten
- **Portal Berita:** Jika pengunjung mengeklik menu Berita, mereka akan disuguhkan daftar artikel yang disusun otomatis berdasarkan urutan *Terbaru*. Berita yang ditandai "Jadikan Berita Unggulan" akan ditaruh di slot paling atas dengan ukuran paling besar.
- **Halaman Unggulan:** Halaman terbit pilihan tampil dalam bagian kecil di beranda; halaman draf/arsip/berjadwal mendatang tidak muncul.
- **Galeri Foto:** Album bertanda unggulan diprioritaskan dalam kisi beranda, diikuti album terbit terbaru. Gambar memakai jalur derivative terkontrol. Watermark bukan perlindungan terhadap penyalinan, crop, resize, atau kompresi ulang.
- **Arsip Dokumen:** Menampilkan tabel sederhana nama berkas dan tombol aksi "Unduh". Semua berkas aman di *server* lokal desa.
- **Peta Interaktif:** Lokasi yang memenuhi syarat publikasi ditampilkan dengan Leaflet dan tile OpenStreetMap; perilaku keyboard/responsif masih menunggu verifikasi browser P7.

## D. Pengamanan (Anti-Spam)
Saat warga mengirim pesan kontak, aplikasi memerlukan verifikasi Turnstile yang berhasil. Bentuk challenge bergantung pada penyedia dan tidak selalu berupa kotak centang. D10 menyetujui inbox Admin saja; notifikasi email otomatis ditunda.

---
**Pesan Penutup:** Segala kemudahan CMS ini dibangun agar Perangkat Desa bisa berfokus pada "Mutu Konten", bukan lagi direpotkan oleh persoalan "Coding". Gunakan instrumen ini sebaik-baiknya untuk mengabarkan kemajuan Desa Kertajaya kepada dunia luar!
