# Master Playbook Deployment: Dewabiz, Cloudflare, VPS & DBeaver

Panduan ini dibuat khusus untuk memandu Anda langkah demi langkah. Setiap tahap memiliki penjelasan **"Fungsi"** dan **"Troubleshooting (Kalau ada error)"**. Ikuti tahap ini secara berurutan dan **laporkan ke saya (AI) setiap kali Anda selesai satu tahap** agar kita bisa evaluasi sebelum lanjut.

---

## TAHAP 1: Dewabiz (Pembelian & Pengaturan Awal Domain)

**Fungsi:** Membeli "alamat rumah" (domain) untuk website Anda agar orang bisa mengaksesnya tanpa harus mengetik deretan angka IP VPS.

1. Buka [Dewabiz](https://dewabiz.com) dan beli domain yang Anda inginkan (misal: `desakertajaya.web.id`).
2. Setelah aktif, masuk ke **Client Area Dewabiz**.
3. Cari menu **Domains** -> klik domain Anda -> cari bagian **Nameservers**.
4. Biarkan tab ini terbuka, kita akan mengubah Nameserver ini dengan milik Cloudflare di Tahap 2.

> **Kalau gagal/bingung:** Biasanya status domain "Pending". Tunggu 1-2 jam sampai statusnya "Active". Jika tidak ada menu Nameserver, lapor ke saya.

---

## TAHAP 2: Cloudflare (Manajemen DNS, Keamanan, & SSL Gratis)

**Fungsi:** Cloudflare bertindak sebagai "satpam" dan "pengatur lalu lintas" antara domain Anda (Dewabiz) dan rumah Anda (VPS). Ini memberikan gembok hijau (HTTPS/SSL) secara gratis dan melindungi dari serangan *hacker*.

1. Buat akun / Login ke [Cloudflare](https://dash.cloudflare.com).
2. Klik **Add a Site**, masukkan domain Anda (contoh: `desakertajaya.web.id`), pilih paket **Free**.
3. Cloudflare akan melakukan *scanning* DNS. Lanjutkan saja sampai Anda diberikan **2 buah Nameservers** (misal: `ns1.cloudflare.com` dan `ns2.cloudflare.com`).
4. Kembali ke tab **Dewabiz** (Tahap 1), ganti Nameserver lama dengan 2 Nameserver dari Cloudflare ini. Klik Save.
5. Kembali ke Cloudflare, klik **Done, check nameservers**.
6. Masuk ke menu **DNS** di Cloudflare:
   - Hapus semua *Record* bawaan yang ada (jika ada).
   - Klik **Add Record**:
     - Type: `A`
     - Name: `@`
     - IPv4 address: `[IP_VPS_ANDA]` (Masukkan IP VPS Anda)
     - Proxy status: **Proxied (Awan Oranye)**
   - Tambah satu lagi untuk www:
     - Type: `CNAME`
     - Name: `www`
     - Target: `desakertajaya.web.id`
     - Proxy status: **Proxied (Awan Oranye)**
7. Masuk ke menu **SSL/TLS**:
   - Pilih mode **Full (Strict)**.

> **Kalau gagal/bingung:** Propagasi DNS (perubahan nameserver) dari Dewabiz ke Cloudflare memakan waktu 5 menit hingga 24 jam. Jika setelah 1 jam awan belum oranye/aktif, lapor ke saya!

---

## TAHAP 3: Persiapan VPS & Pembersihan (Dari Nol)

**Fungsi:** Mengosongkan VPS agar benar-benar bersih dan menginstal semua mesin yang dibutuhkan (PHP, Nginx, PostgreSQL) untuk menjalankan Laravel.

1. Buka PowerShell di laptop Anda, masuk ke VPS:
   ```bash
   ssh root@IP_VPS_ANDA
   ```
2. **Pembersihan Total:** (Menghapus folder web dan drop database lama jika ada).
   ```bash
   rm -rf /var/www/village-cms
   sudo -u postgres psql -c "DROP DATABASE IF EXISTS kertajaya_cms_db;"
   ```
3. **Instalasi Mesin (Web Server, Database, PHP):**
   ```bash
   apt update && apt upgrade -y
   apt install -y software-properties-common
   add-apt-repository ppa:ondrej/php -y
   apt update
   apt install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-tokenizer php8.3-fileinfo php8.3-cli postgresql postgresql-contrib nginx unzip
   curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
   ```
4. **Buat Database Baru:**
   ```bash
   sudo -u postgres psql <<'EOF'
   DROP USER IF EXISTS admin_kj;
   CREATE USER admin_kj WITH PASSWORD 'MASUKKAN_PASSWORD_RAHASIA_ANDA_DISINI';
   CREATE DATABASE kertajaya_cms_db OWNER admin_kj;
   GRANT ALL PRIVILEGES ON DATABASE kertajaya_cms_db TO admin_kj;
   EOF
   ```

> **Kalau gagal/bingung:** Jika saat `ssh` ada error "Connection refused", cek apakah IP VPS sudah benar. Jika instalasi error, lapor ke saya!

---

## TAHAP 4: Upload Kode & Setup Laravel

**Fungsi:** Memasukkan source code (aplikasi buatan kita) dari laptop ke dalam VPS agar bisa diakses online.

1. **Di Laptop Anda (Terminal lokal):**
   Pastikan Anda berada di folder proyek, buat ZIP tanpa folder `node_modules` dan `.git`, lalu kirim ke VPS.
   ```powershell
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   # Buat ZIP secara manual seperti biasa (nama: village-cms.zip)
   scp village-cms.zip root@IP_VPS_ANDA:/root/
   ```
2. **Di VPS Anda:**
   ```bash
   mkdir -p /var/www/village-cms
   mv /root/village-cms.zip /var/www/village-cms/
   cd /var/www/village-cms
   unzip village-cms.zip
   chown -R www-data:www-data /var/www/village-cms
   chmod -R 775 /var/www/village-cms/storage
   chmod -R 775 /var/www/village-cms/bootstrap/cache
   ```
3. **Konfigurasi Lingkungan (.env):**
   ```bash
   cp .env.example .env
   nano .env
   ```
   Ubah bagian ini:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://desakertajaya.web.id

   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=kertajaya_cms_db
   DB_USERNAME=admin_kj
   DB_PASSWORD=MASUKKAN_PASSWORD_RAHASIA_ANDA_DISINI
   ```
4. **Jalankan Aplikasi:**
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan filament:cache-components
   ```

> **Kalau gagal/bingung:** Jika `unzip` command not found, pastikan Tahap 3 sukses. Jika `migrate` gagal, password database di `.env` mungkin salah.

---

## TAHAP 5: Menghubungkan Nginx

**Fungsi:** Nginx adalah satpam pintu masuk di dalam VPS yang memberitahu bahwa "Jika ada yang mencari domain desakertajaya.web.id, arahkan ke folder /var/www/village-cms/public".

1. **Di VPS Anda:**
   ```bash
   nano /etc/nginx/sites-available/village-cms
   ```
2. **Paste Konfigurasi Berikut:**
   ```nginx
   server {
       listen 80;
       server_name desakertajaya.web.id www.desakertajaya.web.id;
       root /var/www/village-cms/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;
       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```
3. **Aktifkan Nginx:**
   ```bash
   ln -s /etc/nginx/sites-available/village-cms /etc/nginx/sites-enabled/
   rm /etc/nginx/sites-enabled/default
   systemctl reload nginx
   ```

> **Kalau gagal/bingung:** Jika `systemctl reload nginx` error, berarti ada salah copy-paste kurung kurawal `{}` di konfigurasi Nginx. Lapor ke saya!

---

## TAHAP 6: DBeaver (Akses Database Visual dari Laptop)

**Fungsi:** Mengatur VPS agar mengizinkan laptop Anda meremote database PostgreSQL, sehingga Anda bisa melihat tabel dan isi database langsung dari aplikasi DBeaver di Windows (tanpa pusing melihat command line).

1. **Di VPS Anda, izinkan PostgreSQL menerima koneksi dari luar:**
   ```bash
   nano /etc/postgresql/16/main/postgresql.conf
   ```
   *(Catatan: Angka 16 menyesuaikan versi PostgreSQL yang terinstall, mungkin 15 atau 14). Cari baris `#listen_addresses = 'localhost'` lalu ubah menjadi (hapus tanda pagarnya):*
   ```conf
   listen_addresses = '*'
   ```
   Save (Ctrl+X -> Y -> Enter).

2. **Izinkan autentikasi password:**
   ```bash
   nano /etc/postgresql/16/main/pg_hba.conf
   ```
   *Scroll paling bawah, tambahkan baris ini:*
   ```conf
   host    all             all             0.0.0.0/0               md5
   ```
   Save (Ctrl+X -> Y -> Enter).

3. **Buka Port di Firewall VPS & Restart Database:**
   ```bash
   ufw allow 5432/tcp
   systemctl restart postgresql
   ```

4. **Di Laptop Anda (DBeaver):**
   - Buka aplikasi DBeaver.
   - Klik logo "Colokan / New Database Connection".
   - Pilih **PostgreSQL**.
   - Isi form koneksi:
     - **Host:** `IP_VPS_ANDA`
     - **Database:** `kertajaya_cms_db`
     - **Username:** `admin_kj`
     - **Password:** `MASUKKAN_PASSWORD_RAHASIA_ANDA_DISINI`
   - Klik **Test Connection**. Jika sukses, klik Finish.

> **Kalau gagal/bingung:** Jika DBeaver error "Connection timed out", berarti firewall VPS belum terbuka untuk port 5432, atau Provider VPS memblokir port tersebut dari panel mereka. Lapor ke saya!
