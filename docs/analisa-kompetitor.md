# Analisa Website Referensi — CMS Bimtek & Diklat

Tanggal analisa: 19 Oktober 2026

## 1. bimtekdiklatnasional.com (Yayasan Bimbingan Teknis Diklatnas)

**Navigasi:** Beranda · Form Pendaftaran · Jadwal Bimtek · Kota Pelaksanaan (dropdown) · Materi Bimtek (dropdown) · Bimtek ESDM · Sertifikasi Rumah Sakit & Kesehatan · Hubungi Kami. Ada kotak pencarian "Cari Materi Bimtek / Kota Pelaksanaan".

**Halaman & struktur data:**
| Halaman | Isi | Implikasi data |
|---|---|---|
| Beranda | Hero, slider artikel ("Baca Sekarang"), grid 35 kota, profil singkat, daftar kategori materi | Artikel, Kota, Kategori |
| Jadwal (`/jadwal-bimtek-2026`) | Tabel Tanggal mulai–selesai + Lokasi, filter Kota & Tahun (2026–2028) | Jadwal = (kota, tgl mulai, tgl selesai); **tidak terikat materi** — jadwal generik per kota, peserta bebas memilih materi |
| Materi per kategori (`/materi-bimtek/{slug}`) | Deskripsi kategori + DataTable daftar materi (No, Judul) dengan search | Kategori → banyak Materi |
| Kota (`/kota/...`) | Halaman per kota (SEO lokal) | Landing page per kota |
| Form Pendaftaran (`/daftar-bimtek`) | Nama, No HP/WA, Instansi, Prov/Kab/Kota → tombol "Hubungi via Whatsapp" | Tidak tersimpan di DB, langsung redirect ke WA |
| Tentang Kami | Profil lembaga | Halaman statis |

**Catatan:** Pola jadwal 4 hari, berulang per minggu di ±35 kota. Tombol WA mengambang ("Klik Disini Hubungi Kami"). Tombol share FB/Twitter/WA di setiap halaman. Kelemahan: tabel materi ESDM kosong ("No data available"), artikel di slider berulang, tidak ada harga, tidak ada data peserta yang tersimpan.

## 2. bimtekdiklat.co.id (Lembaga Kajian Indonesia / LKI)

**Platform:** WordPress (blog post + kategori). Navigasi: Home · Bimtek (dropdown) · Area Bimtek · Bimtek & Ujian Sertifikasi PBJ · Bimtek & Outbound · Tentang Kami · Daftar Online Bimtek. Sidebar: pencarian + daftar kategori.

**Halaman Jadwal** adalah satu *post* statis berisi:
- Narasi legalitas (SKT Kemendagri, akreditasi "B" LKPP) — elemen kepercayaan.
- Daftar kategori materi 2026 (Keuangan, Kepegawaian, Aset/BMD, Pajak, Lingkungan Hidup, PBJ, BLUD, Kearsipan, Pemerintahan, RS & Puskesmas, Desa).
- Tabel jadwal per bulan (rentang tanggal 2 hari) — diketik manual.
- Daftar 10 lokasi.
- **Harga/kontribusi:** Rp5 jt (menginap), Rp4 jt (tidak menginap), Rp1,5 jt (online/Zoom, min. 5 peserta).
- **Fasilitas:** pelatihan 2 hari, menginap 3 malam twin share, ID card, konsumsi, kit & makalah, sertifikat, tas ransel; grup ≥6 orang bisa request tempat/waktu.
- Kontak sekretariat (telepon & HP), konfirmasi H-3.

**Kelemahan:** jadwal di-hardcode dalam teks post (harus edit manual tiap bulan), tidak ada filter, tidak terstruktur, tampilan blog lama.

## 3. Pola Bisnis yang Terlihat

- Target pasar: ASN/PNS, pejabat daerah, DPRD, desa, BLUD, RS — dibiayai anggaran instansi (TA).
- Jadwal **generik** (tanggal × kota), materi dipilih peserta; *In House Training* & request grup sebagai opsi.
- Kanal konversi utama: **WhatsApp**. Konten SEO (artikel per judul materi, halaman per kota/kategori) adalah sumber trafik utama.
- Kepercayaan dibangun lewat legalitas, akreditasi, foto kegiatan, dan sertifikat.

## 4. Rekomendasi Fitur CMS

### Publik (frontend)
1. Beranda: hero + CTA, jadwal terdekat, kategori, kota, artikel, legalitas/testimoni, galeri.
2. **Jadwal dinamis**: filter kota, bulan/tahun, kategori, mode (offline/online); tampilan tabel + kalender.
3. Katalog materi: kategori → materi → detail (deskripsi, sasaran peserta, dasar hukum, narasumber, jadwal terkait, tombol daftar).
4. Landing page per kota & per kategori (SEO-friendly slug, meta, schema.org `Event`/`Course`).
5. **Pendaftaran online tersimpan di DB** (+ opsi lanjut ke WA dengan pesan terisi otomatis): data peserta, instansi, jabatan, NIP (opsional), pilihan jadwal/materi/paket, jumlah peserta (grup).
6. Halaman paket & harga (menginap/tidak/online) + fasilitas — dikelola dari admin.
7. Artikel/blog, galeri kegiatan, tentang kami, legalitas, kontak, FAQ.
8. Cek/unduh sertifikat (verifikasi via nomor/QR) — nilai tambah yang tidak dimiliki kedua kompetitor.
9. Tombol WA mengambang, tombol share, pencarian global.

### Admin (backend)
1. Master data: Kategori, Materi, Kota/Lokasi (hotel/venue), Narasumber, Paket & Harga.
2. Jadwal: CRUD + **generator jadwal berulang** (mis. tiap minggu, durasi N hari, banyak kota sekaligus).
3. Pendaftaran: daftar peserta, status (baru → konfirmasi → lunas → hadir), ekspor Excel, cetak undangan/kuitansi/invoice.
4. Sertifikat: generate PDF massal + nomor & QR verifikasi.
5. Konten: artikel, halaman statis, galeri, banner/slider, menu, SEO meta per halaman.
6. Pengaturan situs: kontak, nomor WA, logo, sosial media, legalitas.
7. Pengguna & peran (Super Admin, Admin, Marketing/CS) + log aktivitas.
8. Dashboard: jumlah pendaftar per jadwal/kota/kategori, lead WA.

### Model data inti (draf)
```
Category(id, name, slug, description)
Topic/Materi(id, category_id, title, slug, description, legal_basis, target_participants)
City(id, name, slug, province) ── Venue(id, city_id, name, address)
Schedule(id, city_id, venue_id?, start_date, end_date, mode[offline|online], topic_id?, quota, status)
Package(id, name, price, description, min_participants)
Registration(id, schedule_id, topic_id, package_id, name, phone, email, institution, position, region, participants_count, status)
Participant(id, registration_id, name, nip, position, certificate_no)
Post(id, title, slug, content, category_id?, published_at)
Page, Gallery, Setting, User/Role
```

## 5. Pertanyaan untuk Menentukan Stack & Lingkup
- Stack pilihan (mis. Laravel + Filament, Next.js + Payload/Strapi, atau WordPress custom)?
- Apakah pendaftaran perlu pembayaran online, atau cukup konfirmasi manual/WA?
- Apakah perlu modul sertifikat & area login peserta?
- Satu brand atau multi-situs (banyak domain lembaga dari satu CMS)?
