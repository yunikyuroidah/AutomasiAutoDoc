# AutomasiAutoDoc

AutomasiAutoDoc adalah aplikasi internal berbasis PHP untuk pengisian data workspace dan pembuatan dokumen otomatis.

## Catatan Penting Terkait Data Sensitif

Folder `template/` sengaja tidak berisi file template asli saat dipublikasikan ke GitHub.

Alasan:
- File template dokumen dapat mengandung informasi sensitif (metadata, identitas instansi, format dokumen internal).
- Untuk keamanan data, file `.doc/.docx/.xls/.xlsx/.xlsm` tidak disertakan di repository.

Silakan simpan template dokumen asli hanya di lingkungan lokal/internal.

## Prasyarat

- PHP 8.2+
- MySQL 8+
- Node.js + npm (hanya untuk menjalankan shortcut `npm run dev`)

## Menjalankan Project (Local)

1. Masuk ke folder project.
2. Jalankan server dev.
3. Buka URL aplikasi.

```powershell
cd D:\laragon\www\AutomasiAutoDoc
npm run dev
```

Akses aplikasi di:

- http://127.0.0.1:8080/

## Akun Demo

- Username: `dummy_admin`
- Password: `dummy_admin_123`

- Username: `dummy_operator`
- Password: `dummy_operator_123`

## Setup Database (Jika Belum Ada)

```powershell
cd D:\laragon\www\AutomasiAutoDoc
$mysql = Get-ChildItem -Path 'D:\laragon\bin' -Filter 'mysql.exe' -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
& $mysql -uroot -e "CREATE DATABASE IF NOT EXISTS autodoc_demo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
cmd /c "\"$mysql\" -uroot autodoc_demo_db < database\autodoc_demo_db.sql"
```

## Struktur Publik Repo

Repository publik hanya menyertakan source code aplikasi dan data dummy. Folder `template/` dipertahankan kosong untuk menjaga kompatibilitas struktur direktori.
