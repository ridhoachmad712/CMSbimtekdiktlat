# Rencana Arsitektur — CMS Bimtek & Diklat

Referensi kebutuhan: [analisa-kompetitor.md](analisa-kompetitor.md)

## 1. Stack

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel 13 (PHP 8.3+) |
| Admin panel | Filament 5 di `/admin` |
| Frontend publik | Blade + Tailwind CSS 4 (Vite), Alpine.js seperlunya; Livewire untuk filter jadwal & form daftar |
| Database | SQLite untuk development, MySQL/MariaDB untuk production |
| Antrian & cache | Driver `database` (cukup untuk shared hosting/VPS kecil), bisa naik ke Redis |
| PDF | `barryvdh/laravel-dompdf` (invoice, sertifikat) — fase 2 |
| Media | Storage `public` disk, `php artisan storage:link` |

Alasan: satu codebase monolit, mudah di-deploy ke VPS/shared hosting, Filament memberi CRUD admin lengkap tanpa membangun dashboard dari nol.

## 2. Struktur Modul

```
app/
├── Enums/                 ScheduleMode, ScheduleStatus, RegistrationStatus
├── Models/                Category, Topic, City, Venue, Schedule, Package,
│                          Registration, Participant, Post, Page, Setting, User
├── Filament/Resources/    Satu resource per model admin (dikelompokkan via navigationGroup)
│   ├── Katalog            Category, Topic
│   ├── Jadwal             City, Venue, Schedule, Package
│   ├── Pendaftaran        Registration (+ relation manager Participant)
│   └── Konten             Post, Page
├── Http/Controllers/      Controller frontend publik (Home, Schedule, Topic, City, Post, Page, Registration)
└── Services/              ScheduleGenerator (jadwal berulang), WhatsAppLink (pesan WA terisi otomatis)
resources/views/
├── layouts/app.blade.php
└── pages/…                Halaman publik
```

## 3. Model Data

```
categories     id, name, slug*, description, icon, sort_order, is_active
topics         id, category_id→, title, slug*, summary, content, legal_basis, target_participants,
               is_featured, is_active, meta_title, meta_description
cities         id, name, slug*, province, image, sort_order, is_active
venues         id, city_id→, name, address, map_url
schedules      id, city_id→, venue_id→?, topic_id→? (null = jadwal umum, materi bebas dipilih),
               start_date, end_date, mode[offline|online|hybrid], quota?, status[open|full|closed|done], notes
packages       id, name, price, description, facilities(json), min_participants, is_active, sort_order
registrations  id, code*, schedule_id→, topic_id→?, package_id→?, contact_name, phone, email,
               institution, position, region, participants_count, notes,
               status[new|confirmed|paid|attended|cancelled], source[web|whatsapp|manual]
participants   id, registration_id→, name, nip, position, phone, certificate_number*
posts          id, category_id→?, user_id→, title, slug*, excerpt, content, image,
               published_at, meta_title, meta_description
pages          id, title, slug*, content, is_published, meta_title, meta_description
settings       id, key*, value(json)   ← kontak, nomor WA, legalitas, sosial media
```

Keputusan penting: **jadwal bersifat generik** (kota × tanggal), seperti kedua kompetitor — `topic_id` opsional; peserta memilih materi saat mendaftar. Ini memungkinkan satu jadwal dipakai untuk puluhan materi.

## 4. Halaman Publik & Rute

| Rute | Halaman |
|---|---|
| `/` | Beranda |
| `/jadwal` | Jadwal + filter kota/bulan/tahun/mode |
| `/materi` , `/materi/{category}` , `/materi/{category}/{topic}` | Katalog materi |
| `/kota/{city}` | Landing page kota (jadwal di kota tsb) |
| `/paket` | Paket & biaya + fasilitas |
| `/daftar` | Form pendaftaran (simpan DB → tampilkan kode + tombol WA) |
| `/artikel` , `/artikel/{post}` | Blog |
| `/{page}` | Halaman statis (tentang, legalitas, kontak) |
| `/sitemap.xml` | Sitemap otomatis |

## 5. Admin (Filament)

- Dashboard: widget statistik pendaftar (hari ini, bulan ini, per status), jadwal terdekat.
- CRUD semua master data, filter & pencarian tabel, ekspor pendaftaran.
- Aksi massal ubah status pendaftaran.
- Generator jadwal berulang (fase 2): pilih kota[], tanggal awal, interval minggu, durasi hari, jumlah ulang.
- Peran: `super_admin`, `admin`, `cs` (fase 2, via `bezhansalleh/filament-shield` atau policy manual).

## 6. Tahapan (Roadmap)

| Fase | Lingkup |
|---|---|
| **1 — Fondasi** (scaffolding ini) | Laravel + Filament, migration & model inti, resource admin, seeder kota/kategori/paket, akun admin |
| 2 — Frontend publik | Layout, beranda, jadwal ber-filter, katalog materi, kota, form daftar + WA, artikel, SEO (meta, sitemap, schema.org) |
| 3 — Operasional | Generator jadwal, ekspor Excel, invoice/kuitansi PDF, notifikasi email/WA, peran pengguna |
| 4 — Nilai tambah | Sertifikat PDF + verifikasi QR, area peserta, galeri, testimoni, multi-situs |

## 7. Deploy

`composer install --no-dev -o` → `.env` (MySQL, `APP_URL`) → `php artisan migrate --force` → `php artisan storage:link` → `npm ci && npm run build` → `php artisan optimize` + `php artisan filament:optimize`. Cron `schedule:run` tiap menit, worker `queue:work`.
