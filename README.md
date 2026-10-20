# CMS Bimtek & Diklat

CMS dan website untuk lembaga penyedia Bimbingan Teknis (Bimtek), Diklat, Workshop, dan In House Training.

- Stack: Laravel 13 · Filament 5 (admin di `/admin`) · SQLite (dev) / MySQL (production)
- Dokumen: [Analisa kompetitor](docs/analisa-kompetitor.md) · [Rencana arsitektur & roadmap](docs/arsitektur.md)

## Menjalankan (development)

```bash
composer install
cp .env.example .env        # lalu: php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka http://localhost:8000/admin. Akun admin dibuat oleh `DatabaseSeeder` dari `ADMIN_EMAIL` / `ADMIN_PASSWORD` di `.env`
(default dev: `admin@example.com` / `password` — **ganti sebelum production**).

Seeder mengisi 35 kota, 15 kategori, 3 paket biaya contoh, halaman statis, pengaturan situs, dan jadwal contoh 8 minggu.

## Modul admin (Fase 1)

| Grup | Resource |
|---|---|
| Pendaftaran | Pendaftaran (+ daftar peserta, aksi ubah status massal, tombol WhatsApp) |
| Jadwal & Lokasi | Jadwal (filter kota/periode/mode, duplikat), Kota, Venue, Paket & Biaya |
| Katalog | Kategori, Materi |
| Konten | Artikel, Halaman |

## Tes

```bash
php artisan test
```
