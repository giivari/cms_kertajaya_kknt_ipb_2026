# Implementation Plan: True Frontend Preview

## 1. Executive Summary
Dokumen ini menguraikan rencana teknis untuk mengubah fitur *preview* di Filament CMS agar menggunakan aliran *frontend* publik (True Frontend Preview). Preview saat ini menggunakan *mockup* komponen di dalam *modal* Filament, yang menyebabkan ketidaksesuaian visual dengan hasil akhir. Solusi ini akan memanfaatkan *iframe* untuk merender rute publik melalui *rendering path* yang sama dengan *frontend* asli, demi mencapai tingkat kesetiaan visual tertinggi yang praktis (*highest practical visual fidelity*).

Penyimpanan sementara menggunakan *ephemeral token store* berbasis *server-side* yang diamankan dengan otentikasi admin Filament.

## 2. Current State
Saat ini, preview diakses melalui `PreviewAction` yang menampilkan modal berisi komponen statis `filament.preview.content`. Data dinormalisasi oleh `PreviewStateNormalizer`. Render tidak menggunakan `layouts.public` maupun *asset* CSS/JS *frontend*, melainkan bergantung pada ekosistem Tailwind milik Filament. Fungsi *temporary media* sudah didukung dan saat ini **sudah mencegah raw path exposure** (path tidak dibocorkan), namun pratinjau belum menggunakan *rendering path* *frontend* yang asli dan dukungan interaktif publik terputus.

## 3. Problem Statement
- **Inkonsistensi Visual**: Pengguna tidak dapat melihat halaman yang dirender secara identik dengan *frontend*.
- **Keterbatasan Responsivitas**: Tampilan responsif hanya dikendalikan ukuran modal Filament, bukan isolasi *viewport* murni.
- **Isolasi Konteks Hilang**: Elemen publik seperti *header*, navigasi, *footer*, dan tipografi tidak ikut dirender.
- **Keterbatasan State Edit**: Data *preview* untuk proses *edit* kehilangan konteks data asli (*snapshot*) dari basis data karena hanya bergantung pada `getRawState()`.

## 4. Goals
- Menyediakan *same frontend rendering path and highest practical visual fidelity*.
- Menampilkan *preview* dalam *fullscreen overlay/modal* dengan *toolbar* responsif (Desktop, Tablet, Mobile) menggunakan ukuran *iframe* yang terkontrol, serta dukungan "Buka di tab baru".
- Menggunakan data *form state* yang digabung dengan *record snapshot* untuk merender halaman.
- Tidak menyisakan jejak konten bisnis (tidak ada record, draft, audit log, atau slug) yang ditinggalkan dari proses *preview*.
- Menjamin isolasi keamanan antar-admin dan dukungan *temporary media* yang aman.

## 5. Non-Goals
- Tidak merombak *business logic* pangkalan data, tidak membuat *draft* persisten.
- Tidak memodifikasi *Contact Messages* atau *frontend form submission*.
- Tidak melemahkan *Security Headers* publik.

## 6. Current Architecture Audit
1. **Pembangunan Preview**: Menggunakan `PreviewAction` yang mengirim data ke `PreviewStateNormalizer` lalu ke `filament.preview.content`.
2. **View Publik Reusable**: Sebagian besar menggunakan Eloquent model (misalnya kueri `News::published()->...`), sehingga membutuhkan strategi *in-memory model* atau *PreviewAdapter* agar tidak perlu menyimpan data di basis data.
3. **Data Dependency**: `AppServiceProvider` via *View Composer* dan `SettingsService` banyak bergantung pada *Database* dan *Cache* global.
4. **SESSION_DRIVER Aktual**: Saat ini `SESSION_DRIVER=database` dikonfigurasi di lingkungan proyek. Penggunaan sesi database standar akan menyulitkan penyimpanan *payload* berukuran sangat besar (mengingat batas kolom `text` atau `payload` pada *session* standar).
5. **Autentikasi Panel**: Rute admin Filament (termasuk *preview*) terlindungi oleh `Authenticate::class` dan `AuthenticateSession::class`.
6. **Security Headers**: Middleware `SecurityHeaders` telah menetapkan `X-Frame-Options: SAMEORIGIN` dan `Content-Security-Policy-Report-Only` (dengan izin terbatas). Pelemahan keamanan global tidak direkomendasikan.

## 7. Preview Transport Evaluation
**A. Encrypted POST Payload**
- **Keamanan**: Sangat baik karena tak ada *state* tertinggal di server.
- **Payload Limit**: Terbatas oleh limit ukuran `POST` dan konfigurasi *web server*.
- **Dukungan Tab Baru**: Sulit. Membutuhkan intervensi JavaScript di sisi klien untuk *re-post* formulir ke tab target. Fungsi *refresh* di tab baru biasanya memicu peringatan *re-submit* *browser*.
- **Temporary Upload**: Sangat rumit karena *file stream* sulit disertakan ulang.
- **Kompleksitas Uji**: Tinggi untuk simulasi *cross-tab*.

**B. Server-Side Ephemeral Token Store (DIREKOMENDASIKAN)**
- **Keamanan**: Terisolasi ketat. Dapat memuat meta: `admin_id`, `session_id`, `created_at`, `expires_at`.
- **Payload Limit**: Dapat dikendalikan secara spesifik (misal, disimpan di `cache` Redis atau *dedicated table* dengan kolom JSON berkapasitas besar).
- **Dukungan Tab Baru**: Mudah (`GET /admin/preview/{token}`). Mendukung *refresh* tanpa peringatan.
- **Temporary Upload**: Stabil karena aset *temporary* dapat diselaraskan kepemilikannya dengan *token* ini.
- **TTL & Cleanup**: Mendukung *Time-To-Live* (TTL) eksplisit dan batasan maksimum *active previews* per admin/session. Mengurangi potensi penumpukan sampah.
- **Klarifikasi Persistensi**: Penyimpanan ini murni *technical temporary persistence*, bukan *business-content persistence*. Data tidak dianggap sebagai bagian dari konten yang bisa diakses publik.

**C. Session-Based Preview State**
- **Keamanan**: Terikat dengan sesi admin.
- **Payload Limit**: Bergantung pada `SESSION_DRIVER` (`database` rentan terhadap isu batasan kolom teks jika konten *rich text* raksasa digunakan).
- **Dukungan Tab Baru**: Mudah.
- **Cross-Admin Isolation**: Otomatis melalui *session ID*.
- **Kompatibilitas**: Memiliki **potensi contention/blocking** (session locking) yang bergantung pada *driver* dan konfigurasi saat melayani *request* konkuren, yang dapat menahan proses *iframe rendering*.

## 8. Proposed Architecture & Preview Data Flow
**Rekomendasi Utama: Server-Side Ephemeral Token Store (Dedicated PostgreSQL Table)**
**Keputusan Arsitektur:**
- Menggunakan *dedicated PostgreSQL ephemeral preview token table*.
- **TTL**: 30 menit.
- **Maksimum token aktif**: 5 per admin dan sesi.
- **Lifecycle**: Token tetap hidup ketika modal ditutup sampai batas TTL (mendukung *refresh* di tab terpisah).
- **PDF Preview**: Menggunakan *safe placeholder* sampai implementasi otentikasi aset sementara di Phase 6.
- **Interaksi Tautan**: Navigasi, *submission*, *download*, dan *action links* akan dibatasi di dalam pratinjau publik (*true preview*), tetapi interaksi UI-only seperti submenu/hamburger menu tetap diizinkan.
- **Namespace**: Komponen konteks utama kelak akan berada di `App\Support\Preview\PreviewContext`.

**Langkah Data Flow:**
1. Admin menekan "Pratinjau". Formulir digabungkan (*merge*): *existing record snapshot* + *current unsaved form state* + *normalized media state*. (Hanya menggunakan `getRawState()` pada saat edit berisiko menghilangkan relasi atau kolom yang tidak diekspos di formulir).
2. Backend membuat `PreviewToken` (*opaque random token* ber-entropi tinggi).
3. Payload (beserta *metadata* admin dan waktu kedaluwarsa) disimpan di *server-side ephemeral store* (mis. Redis/Cache). Dibatasi maksimum N pratinjau per sesi.
4. *Modal* Filament memuat *fullscreen iframe shell*.
5. *Iframe* memanggil `GET /admin/preview/{previewToken}` yang dilindungi oleh perlindungan autentikasi Filament (seperti `Authenticate::class`).
6. `PreviewController` membaca *token*, merekonstruksi `PreviewContext`, dan menyuntikkannya ke *service container*.
7. Tampilan dirender dengan status HTTP `200` dan *header* larangan tembolok (`no-store`, `private`).

## 9. Request-Scoped PreviewContext
Daripada memodifikasi *cache* global atau menebak *state* sesi di dalam *service* yang tersebar, sistem akan menggunakan `PreviewContext` berskala *request*.
`PreviewContext` terdiri dari:
- `preview_type`
- `normalized_state`
- `record_snapshot` (untuk mode edit)
- `settings_override`
- `menu_override_header`
- `menu_override_footer`
- `temporary_asset_resolver`

Layanan `SettingsService`, `AppServiceProvider` (Menu Composer), dan perender publik akan di-refaktor untuk membaca *Context* ini terlebih dahulu sebelum jatuh kembali (*fallback*) ke basis data atau tembolok global. **Tidak ada mutasi ke Cache atau Database global**.

## 10. Strategi Representasi Data & Frontend Reuse Matrix
Sebagian besar tampilan publik mengharapkan instans *Eloquent model*. Menggunakan **In-Memory Eloquent Model (dengan forceFill dan setRelation)** adalah strategi paling kompatibel. Model tidak akan pernah di-`save()`.

- **News**: Diarahkan ke *view* `public.news.show`. Model dibuat di memori. Teks *Draft* / *Scheduled* / *Published* pada *admin toolbar* tetap direpresentasikan jujur sesuai input form (tidak selalu di-*override* menjadi "Published").
- **Page**: Diarahkan ke `pages.dynamic`. Relasi `sections` dikonstruksi menggunakan *collection* dari *in-memory model*.
- **Location**: Diarahkan ke `public.map.show`. *Fallback* *iframe* kosong bila koordinat tidak lengkap.
- **Gallery**: `public.gallery.show`. Menyuntikkan iterasi *items* tanpa *database query*.
- **Document**: `public.documents.show/download`. Tampilan publik yang memiliki tombol "Unduh" akan diganti menjadi *disabled* atau disesuaikan untuk konteks pratinjau agar PDF sementara tidak terunduh otomatis.
- **Menu Header**: Mode ini akan menampilkan beranda utuh dengan bagian *Header* di-*override* oleh form berjalan, sementara *Footer* tetap menggunakan rute dari basis data. Sebaliknya untuk **Menu Footer**. Submenu berfungsi natif menggunakan CSS/JS *frontend*. *Hidden item* tidak tampil. Navigasi tetap merespons berdasarkan ukuran *width* *iframe*. Tautan di *preview* dapat ditekan, namun disarankan diarahkan agar *safe* (tanpa aksi destruktif).
- **Categories (News/Location/Document)**: Dimuat di atas halaman daftar yang aktual (misalnya filter peta atau filter berita). Jangan menyimpan rekam jejak.
- **Website Settings**: Data global dibelokkan (*intercept*) via `PreviewContext` sehingga Logo Desa atau Judul Desa merespons tanpa penulisan *cache*.

## 11. Security Model
- **Auth Guards**: Semua rute `/admin/preview/*` wajib diletakkan dalam ruang lingkup (*middleware group*) dari guard panel Filament (misalnya `Authenticate` dan `AuthenticateSession`), untuk memberlakukan sesi admin, MFA, dll.
- **Frame Policy**: *SecurityHeaders* tidak diperlemah secara global (X-Frame-Options dibiarkan `SAMEORIGIN` jika ada). Namun, pada tanggapan spesifik dari `PreviewController`, jika dibutuhkan *CSP adjustment* atau *header frame-ancestors 'self'*, ini hanya disuntikkan secara *scoped*.
- **Sanitasi Data**: Tag `script:`, *event handler* (`onerror`), dan muatan tidak aman dibersihkan dengan standar yang sama untuk *rich text*.
- **Persistence**: Tidak ada operasi *create/update/delete*, tidak ada efek samping penghasilan *slug*, dan tidak ada pemalsuan log audit.

## 12. Authenticated Temporary Assets (Media)
Menghindari penyimpanan `TemporaryUploadedFile` secara utuh ke *payload* atau membocorkan struktur berkas *livewire-tmp* pada HTML.
- **Skema Endpoint**: `/admin/preview/{previewToken}/assets/{assetToken}`
- **Asset Token**: Opaque random string yang dipetakan oleh peladen (tersimpan pada struktur *token store* `previewToken`) menuju *path* Livewire sesungguhnya.
- **Keamanan Rute**: Tidak menerima variabel eksplisit `{path}`. Rute terlindungi autentikasi admin. Kepemilikan aset dicek berpasangan dengan `previewToken`.
- **MIME & Header**: *Allowlist* terbatas hanya pada `JPEG`, `PNG`, `WebP`, `PDF`. Menjalankan *Server-side MIME verification*, menambahkan respons *header* `Content-Disposition: inline`, `X-Content-Type-Options: nosniff`, dan larangan penyimpanan (`no-store`). Memproteksi celah *path traversal*.
- **HTML Injection**: Saat mengonstruksi pratinjau HTML, semua `media_id` yang belum memiliki rekaman di pangkalan data akan diisi menggunakan *endpoint preview aset* bersertifikasi di atas.

## 13. File Change Matrix (Candidate Assessment)
*(Catatan: Nama berkas bisa berubah menyesuaikan nama aktual sesudah audit langsung tahap implementasi)*
- `app/Http/Controllers/Admin/PreviewController.php` *(Baru)*
- `app/Filament/Support/PreviewContext.php` *(Baru)*
- `resources/views/filament/preview/iframe-shell.blade.php` *(Baru)*
- `app/Filament/Support/PreviewAction.php` *(Diperbarui: Inisiasi Payload & Store)*
- `routes/web.php` atau berkas *route* Filament *(Diperbarui: Penambahan grup admin.preview)*
- `app/Providers/AppServiceProvider.php` *(Diperbarui: Injeksi PreviewContext)*
- `app/Services/SettingsService.php` *(Diperbarui: Injeksi PreviewContext)*

*Catatan Migrasi*: `resources/views/filament/preview/content.blade.php` (Mock lama) dipertahankan selama transisi hingga seluruhnya lulus tes.

## 14. Implementation Phases

**Phase 1 — Preview transport foundation**
- **Exact Scope**: Pembuatan *server-side ephemeral token store* (skema database, migrasi), konfigurasi default (TTL 30 menit, Max 5), pengkodean servis kriptografi (*encryption/decryption*) untuk penyimpanan *payload*. (Perubahan `PreviewAction` digeser ke Phase 2).
- **Files**: Migrasi tabel `preview_tokens`, `App\Services\Preview\PreviewTokenStore.php`.
- **Targeted Tests**: *Invalid/Expired/Cross-admin token rejection*, batas maksimum *payload* 2 MiB, *prune logic*, dan pengujian bahwa token mentah (*raw*) tidak disimpan.
- **Acceptance Criteria**: Data tersimpan terenkripsi dan dapat ditarik kembali secara utuh lewat token rahasia oleh pemiliknya; maksimum 5 token per sesi dipertahankan.
- **Stop Condition**: Token *store* tidak bekerja optimal atau bocor data mentah.
- **Dependency**: Konfigurasi basis data PostgreSQL untuk mendukung ukuran muatan.

**Phase 2 — Fullscreen iframe shell**
- **Exact Scope**: Komponen responsif UI pada jendela *modal* Filament, memodifikasi `PreviewAction` untuk menyimpan ke Token Store dan meluncurkan *modal*. Pembuatan rute pengembali *iframe* (terlindungi autentikasi), pemastian *header* sekuriti yang tepat dan perlindungan *cache*.
- **Files**: `iframe-shell.blade.php`, rute *admin.preview`, `PreviewController.php`, `PreviewAction.php`, `App\Support\Preview\PreviewContext.php`.
- **Targeted Tests**: Akses ditolak jika *guest* atau dari sesi admin berbeda, perlindungan XSS dasar. Respons memuat *headers* HTTP `no-store` dan pengaturan bingkai (`frame-ancestors 'self'`).
- **Acceptance Criteria**: Layar putih aman ditampilkan di dalam antarmuka Filament. Bisa dibuka di *tab baru*.

**Phase 3 — News proof of concept**
- **Exact Scope**: Pembuatan *in-memory* model `News` yang digabung dengan *form unsaved state*, dengan suntikan konten XSS yang disanitasi.
- **Files**: `PreviewController.php`, `PreviewStateNormalizer.php`.
- **Targeted Tests**: Memastikan tampilan *news* asli bisa dirender dengan status Draf (tidak selalu Dipublikasikan secara statis), tiada mutasi basis data/audit log.
- **Acceptance Criteria**: Artikel berita sukses menggunakan `layouts.public` publik seutuhnya tanpa membobol keamanan.

**Phase 4 — Page and Location**
- **Exact Scope**: *Override* Model untuk Builder Page dan Titik Lokasi Peta. Menambahkan perisai koordinat cacat pada peta (menghindari error iframe OpenStreetMap).
- **Files**: `PreviewController.php`.
- **Targeted Tests**: Uji penggabungan *existing record* + modifikasi belum tersimpan. Pastikan relasi komponen halaman termuat.
- **Acceptance Criteria**: Halaman dinamis dapat dimuat penuh bersama peta lokasi.

**Phase 5 — Gallery, Document, and Media**
- **Exact Scope**: Abstraksi data album, manipulasi presentasi dokumen agar rute tidak berusaha mengunduh paksa berkas pratinjau, uji pembuatan ruang muatan galeri kosong.
- **Files**: `PreviewController.php`, sebagian kecil pengecualian pratinjau di komponen `document.blade.php`.
- **Targeted Tests**: Cegah kegagalan halaman publik ketika properti tautan media hilang.

**Phase 6 — Authenticated temporary assets**
- **Exact Scope**: Implementasi *Endpoint* `/admin/preview/{previewToken}/assets/{assetToken}` yang memverifikasi autentikasi dan me-*resolve* *path livewire-tmp* sesungguhnya tanpa ekspos HTML.
- **Files**: Rute `admin.preview.asset`, `PreviewAssetController.php`.
- **Targeted Tests**: Tolak MIME yang tak disetujui, cegah *path traversal*, penolakan lintas-admin, dan *header* pengiriman yang benar (`inline`, `nosniff`).
- **Acceptance Criteria**: Berkas unggahan yang belum tersimpan dapat dirender sebagai `<img src="">` dengan aman di dalam iframe pratinjau.

**Phase 7 — Menu, Categories, and Website Settings**
- **Exact Scope**: Penerapan `PreviewContext` untuk menimpa operasi statis dari konfigurasi `SettingsService` dan *Menu Composer* publik. Uji *fallback* header-only atau footer-only.
- **Files**: `AppServiceProvider.php`, `SettingsService.php`.
- **Targeted Tests**: Modifikasi `village_name` dari *form* tak mempengaruhi setelan situs berjalan. *Submenu* tampil, menu tersembunyi (*hidden*) tidak tampil.
- **Acceptance Criteria**: Preview menu menampilkan halaman *home* dengan bagian header/footer yang diganti datanya secara waktu nyata tanpa mengganggu produksi.

**Phase 8 — Regression, manual visual acceptance, and mock cleanup**
- **Exact Scope**: Melakukan uji regresi penuh, membersihkan berkas lama `content.blade.php`, mengkonversi secara utuh tes *targeted preview* Filament lawas ke metode respons rute baru.
- **Files**: Uji *Feature*, berkas `content.blade.php`.
- **Acceptance Criteria**: Semua kriteria terpenuhi dan lulus persetujuan pratinjau visual manual secara lokal.

*(Catatan: Pekerjaan selalu diletakkan pada cabang/branch proyek berjalan, dieksekusi secara *batch* kecil. Tidak diperkenankan membuat struktur cabang/worktree baru khusus untuk dokumen ini).*

## 15. Test Strategy
Setiap bagian pengembangan harus memastikan lulus pengujian otomatis tanpa kerentanan (*brittle tests*):
- **Authentication**: Penolakan *guest* dan respons *HTTP 403/Redirect*.
- **Token Integrity**: Penolakan `invalid token`, `expired token`, `cross-admin token`, dan `cross-session token`. Penolakan *maximum payload limit* dan *malformed state*.
- **Business Integrity**: **Nol mutasi basis data**, tidak ada *audit log* atau efek penamaan (*slug*). Bebas efek samping *cache* pengaturan situs.
- **Media Security**: Tidak ada jejak *raw path* (mis. `/storage/livewire-tmp/...`) pada *output* HTML, menolak *unsupported preview type* / *MIME rejection*, serta *temporary asset expiry*.
- **Content Accuracy**: Pratinjau mencerminkan struktur menu *(Header-only / Footer-only)* secara tepat. Kombinasi rekam data *(existing record)* dan tambahan ubahan *unsaved* dimuat bersama secara benar. Membersihkan elemen skrip Javascript berisiko dalam muatan XSS. *Refresh* atau *Open New Tab* beroperasi penuh.

## 16. Manual Acceptance Checklist
Penyetujuan visual harus diverifikasi secara manual menggunakan penjelajah web, dengan poin-poin:
- [ ] Resolusi Desktop 1440px / Tablet 768px / Mobile 390px beraksi tanpa memotong konten publik secara tidak lazim.
- [ ] *Toolbar Responsive* teruji bekerja dengan *Iframe Width adjustment*.
- [ ] Dukungan skema *dark/light mode* publik (jika ada) terintegrasi pada iframe.
- [ ] Berita / Dokumen / Galeri / Peta (Koordinat) termuat selaras desain utama tanpa kejanggalan atau kegagalan *scroll* (tidak ada *double scrollbar* kotor).
- [ ] Ikon dan fitur navigasi *frontend* (*header*, *footer*, *submenu*) merespons secara normal tanpa navigasi ke panel luar admin.
- [ ] Berkas unggahan pratinjau (seperti Galeri sementara) terkuantifikasi (Gambar / PDF placeholder) dengan semestinya. Menutup *modal* membuktikan sistem bebas penyimpanan.

## 17. Risks and Mitigations
- **Risiko Keamanan Payload**: Diatasi menggunakan batas wajar muatan *(size limit)* saat penyimpanan *Server-Side Token* agar aplikasi aman dari resiko *Out-Of-Memory* (OOM).
- **Risiko Tabrakan CSS/Script**: Dengan penyusunan arsitektur *Iframe Isolation*, tidak akan ada campuran perpustakaan Tailwind Panel Admin *(Filament)* dengan *Tailwind Frontend* bawaan klien. Keduanya utuh pada lapisnya masing-masing.

## 18. Open Questions
*Area abu-abu yang memerlukan keputusan eksekutif/pengguna sebelum tahap terkait dijalankan:*
1. **TTL Preview Token**: Berapa lama masa berlaku sebuat token pratinjau ditetapkan (misal, 15 menit atau 60 menit)?
2. **Maximum Active Previews**: Berapa batasan wajar maksimum pratinjau yang aktif dalam satu sesi admin (misal, 5 atau 10 pratinjau per admin/sesi)?
3. **Inline PDF Preview**: Apakah PDF akan disajikan *inline* (*browser plugin rendering*) atau cukup menampilkan komponen *placeholder* aman di halaman muka?
4. **Link Interaction**: Di mode pratinjau, apakah perilaku tautan/URL publik akan dibiarkan dapat diklik bebas, dinonaktifkan sepenuhnya secara *event capture*, atau sekadar mengunci tautan ke luar (*external links*)?
5. **Pembersihan Token Modal (*Modal Cleanup*)**: Apakah token secara eksplisit dihancurkan begitu *modal* ditutup dari dalam Filament (berarti tidak dapat diakses ulang di *tab baru* kalau tidak aktif di latar), atau dibiarkan kedaluwarsa secara alami via TTL?

## 19. Recommended First Implementation Batch
Implementasi percontohan awal difokuskan eksklusif pada **Phase 1 (Preview transport foundation)**. Fase ini mengukuhkan pembangunan *store* rintisan untuk memanipulasi *temporary-token*, perlindungan struktur otentikasi (membangun *context parser*), menyiapkan pangkalan kueri yang mutlak aman, tanpa menggabungkan Phase 2 atau perenderan visual pratinjau News.

## 20. Rollback / Failure Handling
Tidak disarankan memakai perintah Git dekstruktif (seperti `reset` atau `checkout --`). Seandainya pergerakan pengamanan *iframe* berdampak pada kesalahan kebijakan origin silang (CORS/CSP) fatal, proyek secara organik akan mundur ke integrasi `content.blade.php` lama dan menangguhkan metode baru melalui *commit* perbaikan iteratif.

## 21. Definition of Done
Modul True Frontend Preview selesai bila *Phase 1* hingga *Phase 8* diaplikasikan penuh dan tidak menimbulkan penurunan keandalan basis, ditutup dengan *regression tests*, pembongkaran artefak statis lama (*mock cleanup*), dan kelulusan *Visual Acceptance Checklist*.
