# 5. Panduan Tampilan Publik (Guest View)

Bagian ini bukan ditujukan untuk mengubah isi sistem, melainkan menjelaskan bagaimana konten yang telah Anda buat di *Panel Admin* dikonversi dan ditampilkan ke pengunjung biasa (Warga Desa atau Tamu dari luar). Memahami alur ini akan membantu Anda mengemas informasi dengan lebih rapi.

## A. Navigasi Atas (Header) & Kaki (Footer)
- Di halaman publik, **Menu Navigasi** yang Anda atur lewat admin akan muncul memanjang di bagian paling atas (*Header*).
- Ikon logo kecil dan nama yang di-klik akan selalu membawa pengguna kembali ke Beranda (Halaman Utama).
- Bagian **Kaki Halaman (Footer)** (paling bawah layar) otomatis memuat Alamat Kantor Desa, Jam Operasional/Email, Tautan Sosial Media, serta Hak Cipta yang ditarik dari "Tampilan & Identitas". Pastikan ejaan Anda benar di Panel Admin, karena akan terekspos di semua halaman.

## B. Halaman Beranda (Home)
Kesan pertama pengunjung ada di sini! Terdiri dari beberapa blok (*section*):
1. **Hero Banner:** Menampilkan Teks Penyambut berukuran besar di atas Gambar Utama (Foto *Landscape* desa yang dipilih di panel admin). Sangat mencolok.
2. **Profil Desa:** Teks singkat berdampingan dengan 2 foto kecil. Sangat cocok diisi dengan ringkasan Visi Misi atau Sejarah Singkat.
3. **Statistik (Angka Bergerak):** Empat kolom khusus menampilkan jumlah penduduk, luasan wilayah, KK, dan Dusun. Angka ini hanya berupa teks biasa yang harus admin perbarui tiap periode, *bukan* ditarik otomatis dari sistem kependudukan (Disdukcapil).
4. **Potensi Desa:** Menampilkan kartu-kartu kecil yang mengarah ke artikel khusus (Misal: Kerajinan, Pertanian).

## C. Kumpulan Daftar Konten
- **Portal Berita:** Jika pengunjung mengeklik menu Berita, mereka akan disuguhkan daftar artikel yang disusun otomatis berdasarkan urutan *Terbaru*. Berita yang ditandai "Jadikan Berita Unggulan" akan ditaruh di slot paling atas dengan ukuran paling besar.
- **Galeri Foto:** Disajikan dalam format album. Saat album ditekan, foto-foto di dalamnya akan mengembang membesar jika diklik, lengkap dengan perlindungan ganda (Tanda Air dan pemblokiran klik-kanan supaya tak bisa disimpan otomatis oleh orang usil).
- **Arsip Dokumen:** Menampilkan tabel sederhana nama berkas dan tombol aksi "Unduh". Semua berkas aman di *server* lokal desa.
- **Peta Interaktif:** Lokasi-lokasi yang ditambahkan di menu "Peta & Lokasi" digabungkan secara otomatis menjadi satu kanvas peta besar (*Google Maps* atau setara), menampilkan sebaran warna-warni yang memudahkan wisatawan atau investor.

## D. Pengamanan (Anti-Spam)
Ketika warga hendak mengirim keluhan melalui fitur *Contact Form*, mereka **wajib** mencentang kotak pengaman (*Turnstile/Captcha*) untuk memastikan mereka adalah manusia sungguhan, bukan robot *Hacker* yang mau mengirim pesan sampah (*spam*) ke *database* balai desa.

---
**Pesan Penutup:** Segala kemudahan CMS ini dibangun agar Perangkat Desa bisa berfokus pada "Mutu Konten", bukan lagi direpotkan oleh persoalan "Coding". Gunakan instrumen ini sebaik-baiknya untuk mengabarkan kemajuan Desa Kertajaya kepada dunia luar!
