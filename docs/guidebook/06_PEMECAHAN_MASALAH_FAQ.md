# 6. Pemecahan Masalah (FAQ) & Skenario "Bagaimana Jika"

Bagian ini memuat daftar kendala yang paling sering ditanyakan oleh pengelola website beserta solusi praktisnya.

---

## 🔐 Skenario Akses & Login

**1. Bagaimana jika saya tidak bisa *login* dan selalu kembali ke halaman masuk?**
- **Sebab:** Ada admin lain yang sedang *login* menggunakan akun Anda di perangkat berbeda (Sistem *Single-Session*).
- **Solusi:** Jika Anda merasa tidak ada yang memakai, kemungkinan Anda lupa *logout* di komputer lain. Tunggu beberapa saat atau hubungi administrator IT untuk me-reset sesi (*session*).

**2. Bagaimana jika saya lupa kata sandi?**
- **Solusi:** Saat ini, sistem terkunci demi keamanan, sehingga Anda tidak bisa mengatur ulang (*reset*) sandi sendiri. Segera hubungi Administrator IT atau pihak pembuat website untuk melakukan pengaturan ulang sandi secara manual di basis data (*database*).

---

## 🖼 Skenario Media & Gambar

**3. Bagaimana jika saya sudah mengunggah gambar di "Perpustakaan Media", tapi gambar tersebut tidak muncul saat saya membuat Berita?**
- **Sebab:** Gambar Anda masih dalam antrean pemrosesan atau statusnya belum *Verified*.
- **Solusi:** Tunggu beberapa detik, lalu muat ulang (*refresh*) halaman (tekan F5). Proses penambahan *watermark* dan kompresi memakan sedikit waktu. Pastikan gambar sudah berstatus **Verified**.

**4. Bagaimana jika gambar yang saya unggah selalu gagal (*Error*)?**
- **Sebab:** Gambar terlalu besar atau formatnya tidak didukung.
- **Solusi:** Pastikan ukuran gambar tidak melebihi **10 MB** (bisa diatur di menu *Tampilan & Identitas*). Format yang diterima adalah JPG, JPEG, PNG, atau WebP.

**5. Bagaimana jika saya ingin menghapus *Watermark* pada gambar tertentu?**
- **Solusi:** Anda **tidak bisa** menghapus *watermark* dari gambar yang sudah terunggah dan berstatus *Verified*. Jika Anda ingin gambar polos, matikan fitur *Watermark* sementara di menu **Tampilan & Identitas**, lalu unggah ulang gambar tersebut dari awal.

---

## 📝 Skenario Pengelolaan Konten (Berita & Halaman)

**6. Bagaimana jika berita yang saya buat salah ketik, apakah bisa diperbaiki setelah tayang?**
- **Solusi:** Tentu saja. Masuk ke menu **Berita**, cari berita tersebut, lalu klik ikon **Pensil (Edit)**. Perbaiki ketikan Anda, lalu klik **Simpan**. Perubahan akan langsung terlihat di website publik saat itu juga.

**7. Bagaimana jika saya ingin menghapus berita secara permanen?**
- **Solusi:** Klik tombol hapus (ikon tempat sampah) pada berita tersebut. Namun, perlu diingat, foto-foto yang pernah Anda lampirkan ke berita itu **tetap akan ada** di Perpustakaan Media dan tidak ikut terhapus.

**8. Bagaimana jika saya membuat Kategori Berita baru, tapi tidak muncul di Beranda?**
- **Solusi:** Kategori tidak memunculkan dirinya sendiri di beranda. Namun, saat warga mengeklik "Berita", mereka bisa memfilter berita berdasarkan kategori baru yang Anda buat.

---

## ⚙️ Skenario Pengaturan & Tampilan

**9. Bagaimana jika saya mengubah Logo Desa, tapi di website publik logonya tidak berubah?**
- **Sebab:** Komputer atau HP Anda masih menyimpan ingatan halaman lama (*Cache Browser*).
- **Solusi:** Di *browser* Anda, tekan tombol **Ctrl + F5** (di Windows) atau **Cmd + Shift + R** (di Mac) secara bersamaan untuk memaksa *browser* memuat versi terbaru dari website.

**10. Bagaimana jika saya memasukkan tautan (URL) di Navigasi, tapi halamannya *Error 404* saat diklik?**
- **Sebab:** Anda mungkin salah mengetik tautan.
- **Solusi:** Pastikan Anda menyertakan garis miring `/` di awal jika menautkan halaman lokal (Contoh: `/berita`). Jika menautkan website luar, pastikan menggunakan awalan `https://` (Contoh: `https://google.com`).

**11. Bagaimana jika ada peringatan "Spam Detected" saat warga mengirim form Kontak?**
- **Sebab:** Warga tersebut belum mencentang atau gagal melewati verifikasi kotak pengaman (*Turnstile*).
- **Solusi:** Arahkan warga untuk mencentang kotak "Saya bukan robot" dengan benar. Jika kotak pengaman tidak muncul sama sekali di website, segera hubungi teknisi IT karena pengaturan kunci *Cloudflare Turnstile* mungkin bermasalah.

---
*Buku panduan ini akan terus diperbarui jika ditemukan kendala baru di masa mendatang.*
