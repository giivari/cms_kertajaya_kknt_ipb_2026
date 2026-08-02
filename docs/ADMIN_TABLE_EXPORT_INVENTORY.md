# Inventaris Ekspor Tabel Admin

Inventaris ini adalah allowlist Batch 3C. Ekspor tidak membaca schema database, `fillable`, atau `toArray()` model secara otomatis. Pencarian, filter, urutan, dan cakupan soft delete berasal dari query tabel Filament yang sedang aktif.

| Tabel admin | Model/query | Filter dan urutan utama | Kolom ekspor yang diizinkan | Data yang sengaja dikecualikan | Soft delete |
|---|---|---|---|---|---|
| Berita | `News` | status, kategori, unggulan, sampah; terbaru | judul, kategori, ringkasan, status, unggulan, tanggal terbit/buat/ubah | slug, isi HTML penuh, media ID/path, payload audit | Ya |
| Halaman | `Page` | status, sampah; terakhir diubah | judul, ringkasan, status, unggulan, SEO aman, tanggal terbit/buat/ubah | slug, builder JSON, media ID/path | Ya |
| Kategori Berita | `NewsCategory` | pencarian nama; urutan nama | nama, deskripsi, jumlah berita, tanggal buat/ubah | slug dan ID internal | Ya |
| Album Galeri | `GalleryAlbum` | status, unggulan, sampah; terbaru | judul, deskripsi, status, unggulan, jumlah foto, tanggal terbit/buat/ubah | slug, media ID/path, data item mentah | Ya |
| Perpustakaan Media | `Media` | MIME, proses, tanda air; terbaru | nama asli, jenis, ukuran, status proses/verifikasi, alt, keterangan, waktu unggah | disk, directory, filename internal, checksum, metadata, path original/derivative | Ya |
| Dokumen | `Document` | sampah | judul, kategori, deskripsi, nama berkas aman, status, unduhan, tanggal terbit/buat/ubah | slug, media ID, disk/path privat | Ya |
| Kategori Dokumen | `DocumentCategory` | pencarian nama | nama, deskripsi, jumlah dokumen, tanggal buat/ubah | slug dan ID internal | Ya |
| Peta dan Lokasi | `Location` | sampah; urutan tampil | nama, kategori, alamat, deskripsi singkat, koordinat, status, urutan, tanggal terbit/ubah | slug, media ID/path, deskripsi HTML mentah | Ya |
| Kategori Lokasi | `LocationCategory` | sampah; urutan tampil | nama, deskripsi, jumlah lokasi, urutan, aktif, tanggal buat/ubah | slug, icon teknis, ID internal | Ya |
| Navigasi | `Menu` | tanpa filter | nama, posisi berlabel Indonesia, keterangan, jumlah tautan, tanggal buat/ubah | URL item mentah, target, foreign key, data nested | Tidak |
| Pesan Masuk | `ContactMessage` | urutan terbaru | nama, jenis/kontak, subjek, pesan, status, waktu baca/arsip/terima | token, session, header request, payload audit | Tidak |
| Log Aktivitas | `AuditLog` | urutan resource | admin, kejadian, jenis/referensi data, IP, waktu | `old_values`, `new_values`, user-agent, cookie, session, auth header, stack trace | Immutable |

## Tabel yang tidak diekspor

- Dasbor dan Pengaturan Website adalah page, bukan tabel data admin.
- Admin tidak memiliki resource daftar akun; credential dan status MFA tidak pernah diekspor.
- Preview Token adalah infrastruktur keamanan, bukan resource admin, dan tidak pernah diekspor.
- Gallery Item dan Menu Item tidak memiliki tabel admin mandiri; datanya dikelola dalam konteks parent dan tidak diekspor sebagai tabel lintas-parent.

## Batas dan penyimpanan

- CSV dan Excel: maksimal 10.000 baris, chunk 100, diproses oleh queue resmi Filament.
- PDF: maksimal 1.000 baris, lalu pengguna diarahkan memakai CSV/Excel. PDF dibuat sinkron, disimpan sementara pada disk privat, dan action Livewire hanya mengarahkan browser ke route download bertanda tangan—byte PDF tidak masuk ke JSON Livewire.
- CSV/XLSX disimpan pada disk privat `local` di direktori tepat `filament_exports/{id}`.
- PDF memakai direktori privat `filament_exports/{id}` yang sama dengan nama internal acak; URL tidak menerima path file dan pemilik diverifikasi dari record `Export`.
- File dan record ekspor yang lebih tua dari 24 jam dipangkas oleh scheduler project-owned.
- Download Filament CSV/XLSX dan route project-owned PDF memerlukan autentikasi, memverifikasi pemilik ekspor, serta mengirim `X-Content-Type-Options: nosniff`.
- Semua teks admin/pengunjung dilindungi dari formula injection sebelum masuk spreadsheet.
