# Etalase Stella

Website etalase karya siswa — PHP + SQLite (database dibuat otomatis, tanpa impor SQL).
Tema merah · abu · putih.

## Cara pakai (XAMPP / Laragon / hosting PHP)
1. Salin folder `stella` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Pastikan PHP 8.0+ dan ekstensi `pdo_sqlite` aktif (bawaan XAMPP).
3. Buka `http://localhost/stella/` — database `data/stella.sqlite` dibuat otomatis.
4. Folder `data/` dan `uploads/` harus bisa ditulis oleh web server.

## Akun awal (GANTI PASSWORD SEGERA di Profil > Edit Profil)
| Peran       | Username    | Password  |
|-------------|-------------|-----------|
| Super Admin | superadmin  | admin123  |
| Admin       | admin       | admin123  |

Siswa mendaftar sendiri lewat halaman **Daftar**.

## Hak akses
- **Siswa**: tambah/edit/hapus karya sendiri, suka, ubah profil.
- **Admin**: semua hak siswa + moderasi karya (hapus, jadikan unggulan) + kelola akun siswa.
- **Super Admin**: semua hak Admin + kelola semua pengguna & peran + Pengaturan Situs (logo, nama, tagline).

## Logo website
Login sebagai Super Admin > **Pengaturan Situs** > unggah logo (PNG transparan/persegi, 512x512 disarankan).
Logo tampil di menu samping, halaman login/daftar, dan ikon tab browser.

## Struktur
- `includes/config.php` — database, autentikasi, fungsi bantu, layout
- `assets/` — CSS & JS
- `data/` — file database (dilindungi .htaccess)
- `uploads/` — gambar karya, avatar, logo (eksekusi PHP diblokir)
