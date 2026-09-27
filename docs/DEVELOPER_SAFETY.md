# Keselamatan pengembangan

[AGENTS.md](../AGENTS.md) adalah kontrak agent. Dokumen ini menjelaskan cara menjalankan guardrail tanpa residu generator lama. Jangan menjadikan hook lokal satu-satunya batas keamanan.

1. Periksa branch, worktree dan `git status` sebelum bekerja. Jangan menimpa perubahan P0–P7 yang belum di-commit. Jangan melakukan `git add .` atau perintah Git destruktif pada data kerja.
2. Pastikan tujuan DB/storage/queue. `.env.testing` dan `.guardrails.local.json` lokal diabaikan Git; jangan mencetak credential atau memindahkan secret ke dokumen.
3. Validasi sebelum tes/build:

   ```powershell
   powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-test.ps1 -ProjectRoot . -ValidateOnly
   powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\guardrails\safe-build.ps1 -ValidateOnly
   ```

4. Jalankan tes terfokus lewat `safe-test.ps1 -ProjectRoot . -TestPaths @('tests/Feature/CategoryTest.php')`; jalankan suite penuh hanya setelah isolation gate dan sesuai scope. Build melalui `safe-build.ps1` bila root/output aman. Jangan menjalankan migrasi/tes pada database kerja/recovery.
5. Gunakan pemeriksaan statis (`php -l`, referensi path/command, `git diff --check`) dan review diff. Browser/visual dan produksi harus dilaporkan sebagai pending bila tidak benar-benar diuji.
6. Untuk kegagalan runtime atau pemulihan, ikuti [RECOVERY](operations/RECOVERY.md): satu unit DB+files+keys+identity dari checkpoint yang cocok, bukan reset DB/storage atau ganti APP_KEY. Jangan otomatis membuka kembali URL media legacy.

Perintah dokumentasi dan contoh bukan otorisasi mengubah server produksi, data kerja, DNS, origin, atau kunci. Status dan gate terkini ada di [PROJECT_STATE](PROJECT_STATE.md).
