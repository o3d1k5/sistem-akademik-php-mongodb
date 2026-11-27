# SIAKAD Sederhana — PHP, MongoDB, Docker, Bootstrap

## Fitur
- Dashboard ringkasan jumlah mahasiswa, dosen, mata kuliah, dan KRS.
- CRUD (Create, Read, Update, Delete) untuk Mahasiswa, Dosen, Mata Kuliah, dan KRS.
- Validasi field wajib dan validasi unik untuk NIM, NIDN, serta kode mata kuliah.
- UI responsif menggunakan Bootstrap 5.

## Menjalankan aplikasi
Pastikan Docker Desktop / Docker Engine dan Docker Compose tersedia.

```bash
docker compose up -d --build
docker compose exec web composer install
```

Buka aplikasi: http://localhost:8080

MongoDB berjalan pada port 27017.
- Username: `admin`
- Password: `admin123`
- Database: `akademik`

## Menghentikan aplikasi
```bash
docker compose down
```
Data MongoDB tetap tersimpan di Docker volume `mongodb_data`.
Untuk menghapus container sekaligus data database:
```bash
docker compose down -v
```

## Struktur
- `index.php` — dashboard, routing, dan modul CRUD.
- `config/database.php` — koneksi MongoDB.
- `Dockerfile` — PHP 8.2 Apache + ekstensi MongoDB.
- `docker-compose.yml` — service web dan MongoDB.
- `composer.json` — library resmi MongoDB PHP.

## Catatan pengembangan
Ini adalah starter project untuk pembelajaran/prototipe, belum untuk produksi. Sebelum produksi, tambahkan autentikasi dan otorisasi, CSRF token, pagination dan pencarian server-side, validasi relasi NIM/NIDN/kode MK, audit log, serta pengelolaan secret melalui environment variable. Untuk deployment, jangan expose port MongoDB ke jaringan publik dan ganti password default.
