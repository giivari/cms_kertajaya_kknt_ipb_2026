# 7. Panduan Teknis & Pemeliharaan (Khusus Administrator/IT Desa)

Buku panduan ini **SANGAT PENTING** dan ditujukan khusus bagi perangkat desa yang ditunjuk sebagai Administrator IT. Panduan ini dirancang sangat mendetail agar Anda (Pihak Desa) **dapat mandiri 100% menyelesaikan masalah teknis tanpa perlu memanggil tim pengembang (developer)**. 

Mohon simpan dokumen ini dengan aman karena berisi perintah-perintah sensitif!

---

## A. Skenario Kritis: Lupa Kata Sandi Admin (Reset Password Mandiri)

Jika admin lupa kata sandi dan tidak bisa masuk ke dalam sistem sama sekali, Anda tidak perlu memanggil *developer*. Ikuti langkah pasti berikut untuk mereset kata sandi langsung dari "Jantung" server (Terminal VPS):

1. **Buka Aplikasi Terminal/SSH** (misalnya menggunakan *Command Prompt* di Windows atau *Terminal* di Mac/Linux).
2. **Login ke Server VPS Desa** dengan mengetikkan perintah berikut (pastikan Anda memiliki *file* kunci rahasia `kunci-desa.pem`):
   ```bash
   ssh -i /path/menuju/kunci-desa.pem kertajaya@103.93.160.112
   ```
   *(Ganti `/path/menuju/` dengan lokasi asli tempat Anda menyimpan kunci pem).*
3. Setelah berhasil masuk ke VPS, **Masuk ke dalam folder website**:
   ```bash
   cd /var/www/village-cms
   ```
4. **Buka Konsol Interaktif Laravel (Tinker)**:
   ```bash
   php artisan tinker
   ```
5. **Ganti Kata Sandi (Ketik kode ini di dalam Tinker)**:
   ```php
   $admin = App\Models\User::where('email', 'admin@kertajaya.desa.id')->first();
   $admin->password = Hash::make('SandiBaruSangatKuat123!');
   $admin->save();
   exit;
   ```
   *(Ganti `admin@kertajaya.desa.id` dengan email admin yang asli, dan `SandiBaruSangatKuat123!` dengan kata sandi baru yang Anda inginkan).*
6. Selesai! Silakan *login* kembali ke web menggunakan kata sandi baru tersebut.

---

## B. Skenario Kritis: Tampilan Website Nyangkut / Error (Clear Cache)

Terkadang, setelah Anda mengubah pengaturan yang sangat banyak, atau server mengalami kepenuhan memori, website bisa menampilkan halaman *Error 500* atau tampilannya berantakan karena "ingatan" (*Cache*) server tersumbat.

**Cara Membersihkan Ingatan Server Secara Mandiri:**
1. Login ke VPS via SSH (Seperti langkah A.1 dan A.2).
2. Masuk ke folder website:
   ```bash
   cd /var/www/village-cms
   ```
3. **Jalankan Perintah "Sapu Bersih" (Clear Cache) Ini Satu Per Satu:**
   ```bash
   php artisan optimize:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   php artisan cache:clear
   ```
4. **Jalankan Perintah "Optimasi Ulang" (Wajib dilakukan di server publik):**
   ```bash
   php artisan optimize
   php artisan view:cache
   ```
5. Website Anda akan kembali segar dan memuat (*loading*) dengan sangat cepat kembali.

---

## C. Skenario Kritis: Gambar Tidak Mau Diproses (Watermark Macet)

Sistem CMS ini menggunakan fitur "Antrean Sinkron" (*Synchronous Queue*) untuk memproses tanda air (*watermark*) dan memperkecil ukuran foto. Ini berarti, saat admin mengklik "Simpan", server akan langsung bekerja keras mengolah foto di detik itu juga.

**Jika foto selalu gagal (Error/Timeout) saat di-upload:**
1. **Sebab 1 (Ukuran File Raksasa):** Pastikan foto yang diunggah dari HP/Kamera tidak berukuran lebih dari 10 MB per foto. Minta admin untuk mengompres foto (misal kirim via WhatsApp dulu lalu diunduh) sebelum di-*upload*.
2. **Sebab 2 (Server Kelelahan):** Masuk ke Panel Admin > **Tampilan & Identitas** > Tab **Pengaturan Lanjutan**.
   - Naikkan angka pada **Batas Waktu Pemrosesan (detik)** dari `120` menjadi `300`.
   - Turunkan **Maksimal Lebar Gambar (px)** jika diatur terlalu tinggi (standar yang baik adalah `1920` atau `2560`).
   - Matikan sementara fitur *Watermark* Terlihat (*Visible Watermark*) di tab **Tampilan**, lalu coba *upload* kembali. Jika berhasil, berarti server butuh RAM lebih besar untuk memproses *watermark*.

---

## D. Cara Melakukan Pencadangan (Backup) Database Berkala

Sangat disarankan bagi IT Desa untuk mencadangkan (*backup*) data secara rutin (misal sebulan sekali) agar data artikel dan keluhan warga tidak hilang jika terjadi musibah pada server.

1. Login ke VPS via SSH.
2. Buat cadangan *database* PostgreSQL menggunakan perintah `pg_dump`:
   ```bash
   pg_dump -U nama_user_db -W -F t nama_database_desa > backup_desa_tanggal_sekarang.tar
   ```
   *(Sistem akan meminta kata sandi database. Masukkan kata sandi database yang ada di dalam file `.env`).*
3. Unduh file `backup_desa_tanggal_sekarang.tar` tersebut ke komputer lokal kantor desa Anda menggunakan aplikasi FTP (seperti FileZilla) atau perintah `scp`.
4. Simpan *file* cadangan tersebut di *Flashdisk* atau Google Drive resmi desa.

---

**Dengan panduan teknis ini, Desa Kertajaya telah memiliki kendali penuh atas sistem informasinya. Semua masalah operasional, kendala lupa sandi, hingga perbaikan performa dapat ditangani secara mandiri oleh tim IT Desa tanpa perlu ketergantungan kepada pihak pembuat sistem.**
