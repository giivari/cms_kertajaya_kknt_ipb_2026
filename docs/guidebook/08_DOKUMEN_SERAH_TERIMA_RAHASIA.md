# 8. Berita Acara Serah Terima (Kredensial Rahasia)

Dokumen ini memuat seluruh kunci akses tingkat tinggi untuk pengelolaan Website dan Server Desa Kertajaya. **Dokumen ini bersifat SANGAT RAHASIA.** Jangan pernah menyebarkan isi dokumen ini, dan serahkan salinan fisiknya/flashdisk-nya secara langsung kepada Kepala Desa atau Perangkat IT yang berwenang.

---

## 1. Akses Utama (Untuk Admin Konten/Operator Web)
Digunakan sehari-hari oleh staf desa untuk mengunggah berita, foto, dan mengatur tampilan web.

- **Tautan Dasbor Admin:** `https://[domain-desa]/desa-dashboard`
- **Email / Username Admin:** `_________________________` *(Isi dengan email resmi)*
- **Kata Sandi Admin:** `_________________________`

---

## 2. Akses Jantung Server (Untuk Teknisi IT Desa)
Digunakan oleh teknisi jika ingin melakukan perbaikan mendalam, *restart* server, atau pembaruan sistem. **Penting:** Pastikan file `kunci-desa.pem` sudah tersimpan aman di dalam *flashdisk* rahasia desa.

- **Alamat IP VPS:** `103.93.160.112`
- **Username VPS:** `kertajaya`
- **File Kunci Akses:** `kunci-desa.pem` (Berada di flashdisk)

*Cara Akses via Terminal:*
`ssh -i /path/menuju/kunci-desa.pem kertajaya@103.93.160.112`

---

## 3. Akses Basis Data (Database)
Digunakan untuk pencadangan (*backup*) data atau pengaturan ulang sistem dari nol.

- **Nama Database:** `_________________________` *(Lihat di file .env)*
- **Username Database:** `_________________________`
- **Kata Sandi Database:** `_________________________`

---

## 4. Akun Layanan Pihak Ketiga (Krusial untuk Tagihan Tahunan)
Sangat penting agar kepemilikan aset digital desa tidak hilang atau hangus tahun depan. Pastikan desa memegang akses ke layanan tempat web ini dihidupkan.

**A. Penyedia Domain (Tempat Nama Web .desa.id Dibeli)**
- Nama Penyedia (Misal: Pandi / Niagahoster): `_________________________`
- Email Login: `_________________________`
- Kata Sandi: `_________________________`

**B. Penyedia VPS/Server (Tempat Server Dihidupkan)**
- Nama Penyedia Cloud: `_________________________`
- Email Login: `_________________________`
- Kata Sandi: `_________________________`

**C. Akun Cloudflare (Anti-Spam & DNS)**
- Email Login: `_________________________`
- Kata Sandi: `_________________________`
- *Fungsi: Melindungi halaman web dari serangan hacker dan spam formulir (Turnstile).*

---

**PERNYATAAN SERAH TERIMA**

Dengan diserahkannya dokumen beserta *Flashdisk* yang memuat *Source Code*, `kunci-desa.pem`, dan `file .env` ini, maka seluruh hak akses, kepemilikan, dan tanggung jawab pengelolaan Sistem Informasi Website Desa Kertajaya telah resmi diserahkan sepenuhnya dari Tim Pengembang/Mahasiswa KKN kepada Pihak Desa Kertajaya.


Kertajaya, ____ Agustus 2026


Pihak Penyerah (Tim KKN),                      Pihak Penerima (Desa Kertajaya),


________________________                       ________________________
NAMA:                                          NAMA:
