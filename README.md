# travel_web_pages

Website perjalanan dengan katalog destinasi dan paket, alur booking, admin CMS, simulasi pembayaran, chatbot, serta globe penerbangan interaktif.

## Fitur

- Halaman Home, Packages, Destinations, About, Contact, dan tiga tahap booking.
- CMS dengan sidebar untuk katalog perjalanan, halaman, FAQ, media, pengaturan, booking, dan pesan.
- Upload gambar dengan preview dan validasi server.
- Globe dengan pola titik daratan, batas negara, rotasi manual, dan simulasi rute great-circle.
- Simulasi pembayaran lokal yang mencatat hasil uji tanpa memindahkan dana atau menandai booking lunas.
- Integrasi layanan eksternal dikonfigurasi melalui environment server.

## Teknologi

Laravel 13, PHP 8.5 pada lingkungan pengujian, MySQL/MariaDB atau SQLite, Blade, JavaScript, dan CSS. Versi dependency lengkap tercatat dalam `composer.lock`.

## Menjalankan secara lokal

```powershell
git clone https://github.com/Roysintax/travel_web_pages.git
cd travel_web_pages
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Untuk katalog MySQL, buat/import database menggunakan `convert/database/travel_hajj.sql` melalui phpMyAdmin. Kemudian sesuaikan konfigurasi `.env` lokal:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=travel_hajj
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi additive dan server dari direktori proyek:

```powershell
php artisan migrate
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000`. Document root Apache harus diarahkan ke folder `public`, bukan root proyek. Folder `convert` menyimpan versi HTML/Express awal dan sumber skema SQL.

## Admin CMS

```powershell
php artisan admin:create admin@example.com --name="Administrator"
```

Command meminta password secara interaktif. Buka `/admin`; tidak ada password admin bawaan. Untuk percobaan lokal tanpa login, atur `CMS_AUTH_ENABLED=false` dan jalankan `php artisan config:clear`. Bypass ini hanya berlaku pada lingkungan local dan akses loopback; default template tetap mengaktifkan autentikasi.

## Template API tanpa kredensial

Nilai rahasia tidak disertakan. Isi konfigurasi hanya pada `.env` lokal:

```dotenv
GROQ_API_KEY=
GROQ_MODEL=openai/gpt-oss-120b
AIRLABS_API_KEY=
AIRLABS_BASE_URL=https://airlabs.co/api/v9
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_CLIENT_ID=
MIDTRANS_CLIENT_SECRET=
MIDTRANS_PUBLIC_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SIMULATION_ENABLED=false
```

Midtrans Snap biasa dan BI SNAP memakai jenis kredensial yang berbeda. BI SNAP masih memerlukan Partner ID, private key pasangan public key merchant, dan konfigurasi metode pembayaran; jangan menganggap mode simulasi sebagai transaksi Sandbox Midtrans yang nyata.

Untuk mencoba simulasi lokal, ubah `MIDTRANS_SIMULATION_ENABLED=true`, jalankan `php artisan config:clear`, lalu isi booking dan lanjutkan ke pemilihan Payment Gateway. Hasil uji bertanda **SIMULASI / BELUM DIBAYAR**. Jangan mentransfer dana ke nomor VA atau QR demonstrasi.

`.gitignore` mengecualikan `.env`, private key, database lokal, cache/log/session/upload runtime, dependency, folder `.agents`, dan `Agent Skills`. File `.env.example` hanya berisi format konfigurasi tanpa nilai API. Kredensial tidak boleh ditempel ke source, README, atau screenshot.

## Pengujian

```powershell
php artisan test --compact
node --test tests/Unit/*.test.js
```

Catatan audit dan hasil verifikasi terdapat di [report/report.md](report/report.md).

## Bukti screenshot

[Folder SS pages](SS%20pages/) berisi 62 screenshot halaman publik, daftar modul CMS, serta contoh form tambah/edit/detail. Halaman yang belum memiliki data ditampilkan dalam kondisi kosong. Beranda yang memiliki animasi scroll juga direkam per bagian.

### Katalog paket

![Halaman paket perjalanan](SS%20pages/02-packages.jpg)

### Globe penerbangan

![Globe dengan pola negara](SS%20pages/01-home-flight-globe.jpg)

### Dashboard CMS

![Dashboard CMS](SS%20pages/09-admin-overview.jpg)

### Upload gambar paket

![Form tambah paket dengan upload gambar](SS%20pages/admin-packages-create.jpg)

## Sumber data globe

Batas negara menggunakan dataset publik [Natural Earth Admin 0 Countries](https://www.naturalearthdata.com/downloads/110m-cultural-vectors/110m-admin-0-countries/) skala 1:110m. Asset disajikan secara lokal di `public/assets/world-countries.geojson`.
