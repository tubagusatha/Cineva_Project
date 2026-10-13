# Cineva — Movie Streaming Platform

Platform movie streaming modern dengan PHP Native, MySQL, dan dark cinematic interface.

## Setup

### Requirements
- XAMPP (Apache + MySQL + PHP)
- Web browser modern

### Installation

1. **Copy project ke XAMPP**
   Pindahkan folder `cineva` ke `C:\xampp\htdocs\`

   ```
   C:\xampp\htdocs\cineva\
   ```

2. **Start XAMPP**
   Buka XAMPP Control Panel, start **Apache** dan **MySQL**.

3. **Import Database**
   - Buka phpMyAdmin di `http://localhost/phpmyadmin`
   - Klik tab **Import**
   - Pilih file `database.sql` dari folder project
   - Klik **Go**

4. **Buat folder uploads** (jika belum ada otomatis)
   ```
   cineva/uploads/posters/
   cineva/uploads/actors/
   ```

5. **Akses Website**
   Buka browser dan kunjungi:
   ```
   http://localhost/cineva/
   ```

## Default Accounts

| Role  | Email              | Password  |
|-------|--------------------|-----------|
| Admin | admin@cineva.com   | admin123  |
| User  | user@cineva.com    | user123   |

## Project Structure

```
cineva/
├── config/           # Database connection
├── includes/         # Reusable components (navbar, header, footer, auth)
├── admin/            # Admin dashboard (CRUD for all entities)
│   └── includes/     # Admin sidebar & header
├── auth/             # Login, register, logout
├── api/              # AJAX endpoints
├── assets/
│   ├── css/          # Stylesheets (style, admin, auth)
│   └── js/           # JavaScript (main, admin)
├── uploads/          # Uploaded images
│   ├── posters/      # Movie posters
│   └── actors/       # Actor photos
├── index.php         # Home page
├── movie.php         # Movie detail
├── watch.php         # Watch trailer
├── search.php        # Search movies
├── genre.php         # Browse by genre
├── profile.php       # User profile
├── history.php       # Watch history
├── database.sql      # Database schema & seed data
└── README.md
```

## Features

**User**
- Browse movies (trending, new releases, top rated)
- Search movies by title/description
- Filter movies by genre
- Movie detail with cast information
- Watch YouTube trailers
- Track watch progress
- View watch history
- User profile management

**Admin**
- Dashboard with statistics
- Full CRUD for: Movies, Genres, Actors, Movie Cast, Users
- View and manage watch history
- Upload poster images and actor photos
- Search and filter in all tables

## Database

Tabel dalam database Cineva:
- `genres` — id, name, description
- `movies` — id, genre_id, title, description, poster_url, year, rating, duration, trailer_url, is_featured, created_at
- `actors` — id, name, bio, url_photo, birthdate
- `movie_actors` — id, movie_id, actor_id, character
- `users` — id, name, email, password, role
- `admins` — id, user_id, name, email, password, status, deleted_at
- `customers` — id, user_id, name, email, password, subscription, subscription_expires_at, active_order_id, payment_method, updated_at
- `subscription_transactions` — id, user_id, order_id, plan, amount, snap_token, payment_type, transaction_status, pdf_url, payment_response, created_at, updated_at, paid_at
- `watch_history` — id, user_id, movie_id, progress, watched_at

## Integrasi Midtrans Payment Gateway

Cineva dilengkapi dengan integrasi pembayaran langganan melalui **Midtrans Snap**:
- **Sandbox & Dummy Ready**: Siap diuji secara instan di XAMPP tanpa perlu kartu kredit asli atau pulsa.
- **Dukungan Metode Pembayaran**: QRIS (GoPay, OVO, Dana, ShopeePay), Transfer Bank (Virtual Account BCA, Mandiri, BNI, BRI), Kartu Kredit/Debit.
- **Simulator Dummy Bawaan**: Jika Anda menguji di localhost secara offline atau tanpa koneksi API, terdapat Simulator Dummy interaktif yang langsung mengaktifkan langganan dan mencatat riwayat transaksi.
- **Auto-Migration**: Tabel `subscription_transactions` dan kolom masa aktif di `customers` dibuat secara otomatis saat web diakses.

### Konfigurasi Midtrans

Buka `config/midtrans.php` jika ingin mengganti kredensial Midtrans Anda:
```php
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-XXXXX');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-XXXXX');
define('MIDTRANS_IS_PRODUCTION', false); // false untuk Sandbox
```

### Konfigurasi Database

Edit `config/database.php` untuk mengubah koneksi database XAMPP:
```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cineva';
```
