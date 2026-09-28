# Pengembangan lokal dan keselamatan data

Script `scripts/local/start-local.ps1` dan `stop-local.ps1` adalah **khusus workstation historis**: source saat ini menunjuk cluster PostgreSQL bernama recovery di luar repository serta database kerja tertentu. Jangan menjalankannya untuk tes, rehearsal, atau sebagai contoh instalasi umum. Dokumen ini tidak menyalin path/credential historis. Pengguna harus memeriksa targetnya secara eksplisit sebelum memakai script lokal itu pada data kerja.

## Profil tes disposable

Tes P6/P7 yang tercatat memakai `.env.testing`, `.guardrails.local.json` (keduanya diabaikan Git), PostgreSQL `village_cms_test` pada loopback port tes dan cluster disposable di `storage/testing/`. Nilai identitas harus divalidasi setiap kali, bukan disimpulkan dari `APP_ENV` saja. `tests/TestCase.php` memakai storage fake untuk disk `local`, `public`, dan `admin_exports`; tes file lain harus tetap memeriksa semua root, symlink, queue dan konfigurasi yang dipakai.

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot . -ValidateOnly
# Setelah cluster disposable dan semua storage dipastikan benar:
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot . -TestPaths @('tests/Feature/CategoryTest.php')
```

Wrapper adalah jalur wajib tes. Jangan memakai database kerja/recovery, menjalankan `migrate:fresh`/seeder pada keduanya, atau menganggap `.env.testing` mengisolasi setiap path file. Jangan menjalankan `media:lifecycle-inventory --env=testing` sebelum root `public` juga terbukti terisolasi: command itu pernah dapat melihat nama file media kerja. Hentikan hanya cluster disposable yang benar-benar Anda mulai setelah tes.

## Build dan operasi lokal

`scripts/guardrails/safe-build.ps1 -ValidateOnly` memeriksa prasyarat build tanpa mengubah aset; build melalui wrapper hanya bila target artefaknya aman. `public/hot` milik Vite development dan tidak boleh terbawa ke release. P5 memakai queue database untuk CSV/XLSX; caller media image saat ini memakai `dispatchSync()` sehingga berjalan dalam request, bukan worker. Baca [worker/scheduler](operations/QUEUE_AND_SCHEDULER.md). Preview bukan simpan, ekspor berada di storage privat, dan route cache target harus dibuat ulang; jangan mengedit artefak generated.

Untuk instalasi baru, update, dan pemulihan gunakan [DEPLOYMENT_GUIDE](DEPLOYMENT_GUIDE.md) serta [RECOVERY](operations/RECOVERY.md). Jangan mengandalkan backup database saja atau memutar kunci instalasi saat update. D09 belum memilih jadwal/retensi backup.
