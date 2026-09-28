# 1. Pengantar dan Akses Admin

Selamat datang di Buku Panduan Sistem Informasi Desa Kertajaya (CMS). Buku ini ditujukan bagi Perangkat Desa atau Admin yang bertugas mengelola konten website resmi desa.

## Cara Masuk (Login) ke Panel Admin
Panel Admin adalah ruang kendali rahasia yang hanya bisa diakses oleh pengurus desa.

1. Buka *browser* (Chrome, Firefox, dsb.) di komputer atau HP Anda.
2. Ketik alamat website desa dengan tambahan `/desa-dashboard` di belakangnya (Contoh: `https://desakertajaya.web.id/desa-dashboard`).
3. Anda akan disambut oleh halaman Login.
4. Masukkan **Username** dan **Kata Sandi** yang sah.
5. Klik **Masuk**.

> **Keamanan akses:** Instalasi memakai satu akun Admin dan MFA wajib. Sandi baru minimal 12 karakter dengan huruf besar, huruf kecil, dan angka; simbol disarankan. Setelah kegagalan login, penundaan meningkat 1/3/5/10 detik; kegagalan kelima mengunci pasangan identitas-klien selama 15 menit. Perubahan sandi/email/username meminta sandi saat ini dan TOTP aktif pada setiap simpan. Sesi tetap diperiksa; selalu logout pada perangkat bersama. D02 menyetujui pemulihan hanya oleh operator berwenang, tanpa recovery code atau endpoint tamu; lihat [runbook](../operations/RECOVERY.md).

Jika perangkat authenticator hilang tetapi Anda masih mengetahui sandi, hubungi operator resmi. Setelah pemulihan MFA oleh operator, masuk dengan sandi yang sama dan ikuti enrolmen authenticator baru yang diwajibkan panel. Jangan meminta operator mengirim kode atau secret melalui pesan; [prosedur operator](../operations/RECOVERY.md) menjelaskan kontrolnya.

## Tampilan Utama (Dasbor)
Setelah *login*, halaman yang pertama kali muncul adalah Dasbor.

- Di sebelah kiri layar adalah **Menu Utama** (Bilah Sisi) yang berisi semua alat untuk mengelola website (seperti Berita, Halaman, Pengaturan, dll).
- Di pojok kanan atas, terdapat ikon profil Anda untuk mengubah pengaturan sandi dan tombol **Keluar (Logout)**. Selalu gunakan tombol ini setiap selesai bertugas!
