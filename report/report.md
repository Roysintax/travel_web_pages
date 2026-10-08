# Laporan Audit SQA — Travel Web

Tanggal: 7 Oktober 2026 (Asia/Barnaul)  
Peran: Senior Software Quality Assurance Engineering  
Hasil: perbaikan keamanan dan pembersihan kode selesai untuk temuan terverifikasi; persetujuan rilis produksi masih bersyarat pada tindak lanjut di bawah.

## 1. Ruang lingkup dan metode

Audit mencakup kode aplikasi Laravel pada `app/`, `routes/`, `bootstrap/`, konfigurasi, migration, Blade, JavaScript di `public/`, test, konfigurasi akses Apache, serta server Express terpisah di `convert/server/`. Pemeriksaan mengikuti lima dimensi: correctness, readability, architecture, security, dan performance.

Metode: membaca alur request dan test, memeriksa referensi kode, mereproduksi masalah melalui test yang gagal, memperbaiki implementasi, menjalankan test regresi, melakukan satu mutation check, audit dependensi, dan smoke test browser. Akses file sensitif Apache diperiksa menggunakan HEAD; isi `.env` dan database tidak diunduh.

Kode vendor, `node_modules`, aset gambar/video, dan materi skill tidak diperlakukan sebagai kode aplikasi untuk penghapusan. Folder `convert/` dipertahankan karena memiliki entry point, server, dan data sumber migrasi tersendiri. Tidak adanya referensi statis bukan bukti cukup untuk menghapus relationship Eloquent atau data katalog.

### Lingkungan terverifikasi

| Komponen | Versi / kondisi |
| --- | --- |
| PHP CLI | 8.5.11 |
| Laravel framework | 13.35.0 |
| PHPUnit | 12.5.38 |
| Laravel Pint | 1.32.1 |
| Laravel Boost | 2.10.2 terpasang; tool MCP tidak tersedia dalam sesi |
| Node.js | 24.19.0 |
| Dependensi frontend utama | Rentang versi tercantum di `package.json`; tidak ada lockfile maupun instalasi Vite lokal yang dapat dipakai |
| Version control | Direktori ini bukan Git repository; tidak ada diff, commit, atau audit riwayat Git |
| Aturan tambahan | `.ai/rules` tidak ditemukan; mengikuti `AGENTS.md` dan skill yang relevan |

API Laravel diperiksa terhadap dokumentasi versi 13 dan kode framework terpasang. Tidak ada dependency yang ditambahkan atau diperbarui. Tidak ada migration atau perubahan pada data katalog produksi.

## 2. Threat model

| Batas kepercayaan | Aset / ancaman | Kontrol yang diterapkan |
| --- | --- | --- |
| HTTP Apache → filesystem proyek | Pengungkapan kredensial, kode sumber, database, dan dump SQL | Root proyek ditutup; `public/` dibuka secara eksplisit |
| Browser → booking | Perubahan booking milik sesi lain dan data paket fiktif | Kepemilikan sesi dan pemeriksaan paket aktif pada server |
| Browser → contact / chat | CSRF, spam, konsumsi kuota AI | CSRF Laravel dan rate limit per endpoint |
| Token klien → penyimpanan chat | Penambahan pesan ke sesi orang lain | Token penyimpanan diturunkan dengan HMAC dari sesi server dan token klien |
| Database katalog → DOM | Stored XSS melalui teks dan atribut HTML | Escaping semua nilai dinamis pada template kartu dan modal |
| Provider AI → aplikasi / log | Payload malformed, kebocoran teks pribadi pada log, redirect request berotorisasi | Validasi tipe jawaban, logging metadata, batas waktu, redirect dinonaktifkan |

## 3. Temuan dan perbaikan

| ID | Prioritas | Bukti sebelum perbaikan | Perbaikan / status |
| --- | --- | --- | --- |
| SEC-01 | Critical | Apache lokal mengembalikan HEAD 200 untuk `.env`, `composer.json`, dan `database/database.sqlite` | Ditambahkan `.htaccess` root dengan `Require all denied`; `public/.htaccess` mengizinkan direktori publik. Pemeriksaan ulang: file tersebut 403; halaman dan CSS publik 200 |
| SEC-02 | High | PATCH status hanya mencari `reference_code`; request setelah sesi pembuat dibersihkan tetap berhasil dan mengembalikan model booking | Referensi booking yang dibuat dicatat dalam sesi server, maksimum 50 terbaru; request sesi lain menerima 404 tanpa data booking |
| SEC-03 | High | Nilai database seperti title, destination, features, itinerary, hotel, ID, dan atribut gambar dimasukkan ke `innerHTML` tanpa escaping | `escapeHtml()` digunakan pada nilai dinamis kartu/modal; payload markup berbahaya diuji pada renderer JavaScript sebenarnya |
| SEC-04 | High | Seluruh `api/*` dikecualikan dari CSRF, termasuk chat berbiaya | Pengecualian dihapus. Token tidak cocok/tidak ada menghasilkan 419; token cocok tetap dapat digunakan. Frontend sudah mengirim header CSRF |
| SEC-05 | High | Contact, booking, dan chat tidak memiliki throttle Laravel | Per IP, per menit: contact 10, booking creation 10, status booking 30, chat 20. Prefix limiter dipisahkan untuk mencegah saling menghabiskan kuota |
| SEC-06 | Medium | Body error provider dan pesan exception dicatat mentah; dapat berisi data pribadi atau detail request/database | Laravel hanya mencatat status atau kelas exception. Service Groq Express hanya mencatat status; detail exception jaringan tidak diteruskan |
| SEC-07 | Medium | `session_token` klien langsung menjadi identifier database sehingga dapat menambah pesan ke sesi yang sudah ada | Controller memakai HMAC-SHA256 berbasis ID sesi server, token klien, dan APP_KEY; test membuktikan sesi target tidak berubah |
| SEC-08 | Medium | Header keamanan tidak tersedia pada respons Laravel | Middleware menambahkan CSP, nosniff, DENY framing, referrer policy, permissions policy, dan HSTS pada HTTPS |
| SEC-09 | Medium | Reference code backend hanya memiliki 9.000 kemungkinan suffix per tahun | Diganti suffix acak 24 karakter dari `Str::random()`; tetap muat di kolom 40 karakter. Referensi bukan lagi bukti kepemilikan |
| BUG-01 | High | Paket tidak ditemukan atau tidak aktif tetap menghasilkan booking; fallback menggunakan package ID 1 dan harga Rp19.500.000 | Query paket aktif mengelompokkan booking key, slug, dan ID. Tidak tersedia → 422; harga dan currency berasal dari paket server |
| BUG-02 | Medium | `reply: null` lolos validation tetapi menghasilkan SQL NOT NULL error / HTTP 500 | Nilai null memakai channel `Email`; test memeriksa respons dan database |
| BUG-03 | Medium | Jawaban AI berupa whitespace menjadi respons kosong; content non-string tidak ditangani secara eksplisit | Laravel memakai fallback lokal jika content bukan string bermakna. Express menghasilkan service error 502 untuk content malformed |
| BUG-04 | Medium | Test Express mengimpor `together.service.js` yang tidak ada; health mengecek TOGETHER_API_KEY sementara service memakai Groq | Import dan nama environment diselaraskan dengan Groq. Test lama kembali berjalan; referensi provider lama pada kode runtime dibersihkan |

Tambahan pada service Laravel: connection timeout 5 detik dan larangan redirect. Service Express menggunakan `redirect: 'error'`. Perubahan ini mencegah mengikuti redirect provider secara otomatis; endpoint provider berasal dari konfigurasi server, bukan input URL pengguna.

`.gitignore` diperluas untuk `.env.*`, dengan pengecualian `.env.example`, serta file `.pem` dan `.key`. Ini melindungi penambahan file sensitif saat version control nanti digunakan, tetapi tidak menghapus file yang sudah terlanjur dilacak di repository lain.

## 4. Dead code yang dihapus

| Elemen | Bukti penghapusan |
| --- | --- |
| `resources/views/welcome.blade.php` | Tidak ada route/controller yang merender template scaffold ini; homepage menggunakan `home` |
| Dataset fallback besar di `public/js/packages.js` | Halaman Laravel selalu memasok `window.PACKAGES_DATA`, termasuk array kosong; dataset duplikat tidak digunakan dalam alur tersebut. Fallback sekarang array kosong |
| `ChatSession::createWithToken()` dan import `Str` pada model | Tidak ada pemanggil dalam kode aplikasi/test; penyimpanan chat memakai jalur service |
| `SiteSetting::getValue()` | Tidak ada pemanggil; halaman Contact menggunakan `allKeyValues()` |
| `Booking::getFormattedTotalAttribute()` | Tidak ada penggunaan method maupun atribut `formatted_total`; frontend menghitung tampilan dari paket preview |
| Import `MustVerifyEmail` yang dikomentari pada `User` | Tidak digunakan dan bukan deklarasi aktif |
| Import test ke service Together yang hilang | Diganti dengan service Groq aktif, bukan menambahkan compatibility shim |

Dipertahankan: route redirect HTML yang memiliki test dan digunakan link lama; model/relationship domain; migration dengan `down()` kosong yang sengaja melindungi data hasil import; input Vite yang masih tercantum di konfigurasi build; test yang sudah ada. Pembersihan ini tidak menyatakan bahwa setiap kemungkinan kode tidak terpakai secara dinamis telah terbukti hilang.

## 5. Hasil review kualitas

### Correctness

Validasi paket kini tidak membuat booking dengan harga/foreign key fiktif. Booking key, slug, dan ID aktif tetap didukung. Test write operation memeriksa respons dan database. Bug contact null serta jawaban AI kosong/malformed diperbaiki. Alur browser booking tetap merupakan preview berbasis sessionStorage dan tidak otomatis memanggil POST booking Laravel.

### Readability dan architecture

Pola controller, model, dan Form Request yang ada dipertahankan. Tidak ditambahkan framework autentikasi, dependency sanitizer, atau service layer baru. Middleware keamanan dibuat melalui Artisan. Transformasi paket diberi tipe Collection dan PHPDoc array shape. Data fallback katalog yang menduplikasi database dihapus.

### Security

Query request yang diperiksa menggunakan binding Eloquent; tidak ditemukan query SQL atau shell yang dibangun dengan interpolasi input pengguna pada kode runtime Laravel. Payload yang dipersist tetap dipilih per field. Output chat menggunakan textContent pada frontend; raw Blade di homepage berisi literal statis, bukan input request/database. Temuan XSS yang nyata berada pada renderer katalog dan sudah diperbaiki.

### Performance

Home, Destinations, dan Packages sudah eager-load relationship utama. Katalog masih menggunakan `get()` tanpa pagination dan filtering di browser; cocok untuk katalog kecil saat ini, tetapi berpotensi meningkatkan ukuran HTML/JSON dan memori saat data bertambah. Tidak ada load test atau angka Core Web Vitals yang diukur, sehingga tidak diberikan klaim peningkatan performa kuantitatif.

## 6. Verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| Baseline `php artisan test --compact` | PASS — 36 test, 174 assertion |
| Reproduksi backend sebelum perbaikan | FAIL sesuai masalah: paket tidak tersedia diterima, akses booking lintas sesi, CSRF bypass, header hilang, throttle hilang, log error, content whitespace |
| Reproduksi contact null dan token chat | FAIL — null menghasilkan 500; pesan masuk ke sesi target |
| Reproduksi JavaScript XSS | FAIL — payload menghasilkan markup aktif di kartu katalog |
| Reproduksi Express | Awalnya gagal import service; setelah import benar, dua test keamanan gagal untuk log body dan content non-string |
| Final `php artisan test --compact` | PASS — **53 test, 269 assertion** |
| `node --test convert/server/tests/chat.test.js tests/Unit/PackageCatalog.test.js` | PASS — **5 test**, tidak skipped |
| Mutation check otorisasi | Mengganti sementara `abort_unless` dengan `abort_if` membuat test pemilik sesi gagal. Kode dipulihkan dalam `finally`; suite final lulus |
| `composer audit --locked --format=json` | PASS — advisory kosong, abandoned kosong |
| `npm audit --prefix convert/server --json` | PASS — 0 vulnerability pada lockfile server Express |
| `npm audit --json` di root | Tidak dapat menilai — ENOLOCK, lockfile utama tidak ada |
| `npm run build` di root | Gagal — executable Vite tidak terpasang. Tidak menginstal atau mengubah dependency |
| Pint | PASS pada seluruh file PHP yang diubah, menggunakan `--format agent` |
| Apache `httpd.exe -t` | Syntax OK |
| Apache HEAD sesudah hardening | `.env`, composer metadata, database SQLite, dan dump SQL: 403; halaman/CSS `public/`: 200 |
| Browser in-app, Packages | 6 kartu ter-render; modal itinerary Yunani terbuka dengan data yang benar; log error/warn yang diperiksa kosong |
| Browser in-app, Contact | Halaman ter-render; log error/warn yang diperiksa kosong |

Pint `--dirty` dicoba tetapi tidak tersedia karena direktori bukan Git repository; pemformatan dilakukan dengan daftar eksplisit file yang diubah. PHPUnit/Pint awalnya terhalang pemeriksaan `is_readable`/`is_writable` dalam sandbox; dijalankan ulang dengan akses yang disetujui. Composer audit memerlukan akses jaringan ke Packagist. Tidak ada pemeriksaan yang gagal tersebut dianggap sebagai hasil lulus.

Test HTTP menggunakan database SQLite in-memory. Integrasi provider diuji dengan fake HTTP/fetch; tidak mengirim data pengguna atau melakukan pembayaran nyata. Test JavaScript memeriksa HTML keluaran renderer dalam Node VM dengan DOM stub; smoke test browser melengkapi pemeriksaan runtime normal, tetapi bukan bukti lengkap eksploitasi XSS lintas browser.

## 7. Keputusan dan konsekuensi

1. **Kepemilikan booking berbasis sesi.** Aplikasi belum memiliki login pelanggan. Sesi pembuat dipilih sebagai batas akses minimum tanpa menambahkan flow autentikasi baru. Setelah sesi hilang/expired, booking lama tidak dapat diubah melalui endpoint tersebut. Maksimum 50 referensi terbaru dipertahankan; akses lintas perangkat memerlukan desain akun atau mekanisme pemulihan terpisah.
2. **Token chat terikat sesi server.** Token klien tetap diterima, tetapi tidak lagi mengacu langsung pada baris database. Sesi server berbeda menghasilkan identifier penyimpanan berbeda. Data chat lama tidak dimigrasikan.
3. **CSP kompatibel dengan UI sekarang.** Origin script/style/font/map dibatasi dan object/framing diblokir. `unsafe-inline` masih diperlukan oleh inline script dan runtime CSS yang ada; kebijakan ini belum setara CSP berbasis nonce/hash. Escaping DOM tetap kontrol utama terhadap temuan XSS.
4. **Akses Apache hanya melalui public.** Akses langsung ke root proyek dan `convert/` melalui Apache sengaja ditutup. Penggunaan virtual host sebaiknya menetapkan DocumentRoot ke `public/`. Server Express standalone tetap terpisah dari aturan Apache ini.
5. **Tidak menghapus data/domain secara spekulatif.** Arsip konversi, katalog, migration, dan relationship dipertahankan karena memiliki fungsi atau bukti penggunaan dinamis yang tidak cukup untuk penghapusan aman.

## 8. Tindak lanjut sebelum produksi

| Prioritas | Pekerjaan tersisa | Alasan / kriteria selesai |
| --- | --- | --- |
| High | Evaluasi paparan rahasia sebelumnya | HEAD 200 membuktikan akses file, bukan bahwa pihak lain sudah membacanya. Jika host pernah dapat diakses pihak lain, evaluasi access log dan rotasi kredensial yang terpapar. Proteksi file sekarang tidak membatalkan paparan lama |
| High | Tetapkan konfigurasi produksi dan document root | Lingkungan yang diperiksa masih `APP_ENV=local`, `APP_DEBUG=true`. Produksi harus menonaktifkan debug, memakai HTTPS, secure cookies, serta hanya mengekspos `public/`. Proteksi `.htaccess` perlu dipertahankan; Nginx memerlukan konfigurasi ekuivalen |
| High | Pulihkan build frontend yang reproducible | Tentukan versi paket yang disetujui, buat/commit lockfile utama, install dengan kebijakan script yang sesuai, jalankan audit dan build. Audit Express tidak mewakili dependency root |
| Medium | Kebijakan retensi data pribadi | `expires_at` chat belum memiliki proses penghapusan terjadwal; contact/booking juga belum memiliki retensi/erasure policy. Tetapkan durasi dan cakupan termasuk backup sebelum menghapus data |
| Medium | CSP nonce/hash dan aset produksi | Hilangkan kebutuhan `unsafe-inline` dan runtime CDN CSS dengan pipeline yang terverifikasi; ulangi smoke test seluruh halaman setelah perubahan |
| Medium | Kontrol deployment dan abuse pada beberapa instance | Pastikan limiter Laravel memakai cache bersama dan konfigurasi proxy/IP benar. Limiter Express masih in-memory dan hanya sesuai satu proses |
| Medium | Batasi ukuran katalog saat data tumbuh | Gunakan pagination/server filtering setelah kebutuhan UX dan ukuran data disepakati; ukur query/payload terlebih dahulu |
| Medium | Test browser tambahan | Mobile, keyboard/focus trap, navigasi booking lengkap, error jaringan, serta simulasi payment belum diverifikasi secara menyeluruh |
| Low | Mutu test scaffold dan dokumentasi lama | Test `true === true` tidak memberikan coverage bisnis; README masih scaffold. Dipertahankan agar perubahan audit tidak menghapus test atau menulis dokumen tambahan tanpa kebutuhan jelas |

Tanggal review berikutnya untuk item tertunda: sebelum rilis berikutnya, paling lambat 14 Oktober 2026. Tidak ada deployment dilakukan. Laporan ini tidak menyatakan production-ready tanpa penyelesaian item High dan verifikasi lingkungan deployment.

## 9. Referensi resmi

- [Laravel 13 — Validation](https://laravel.com/framework/docs/13.x/validation)
- [Laravel 13 — HTTP Tests](https://laravel.com/framework/docs/13.x/http-tests)
- [Laravel 13 — CSRF Protection](https://laravel.com/framework/docs/13.x/csrf)
- [Laravel 13 — Routing dan Rate Limiting](https://laravel.com/framework/docs/13.x/routing)
- [Laravel 13 — HTTP Client](https://laravel.com/framework/docs/13.x/http-client)
- [Laravel 13 — Middleware](https://laravel.com/framework/docs/13.x/middleware)
- [Laravel 13 — HTTP Session](https://laravel.com/framework/docs/13.x/session)
- [Laravel 13 — Deployment](https://laravel.com/framework/docs/13.x/deployment)
- [Apache 2.4 — Require dan AuthMerging](https://httpd.apache.org/docs/2.4/mod/mod_authz_core.html)

## 10. Implementasi CMS admin — 7 Oktober 2026

CMS Laravel kini tersedia pada `/admin/login`, dengan sidebar navy, aksen teal, dashboard data aktual, pencarian, pagination, status filter, formulir validasi, dan dialog konfirmasi hapus. Sidebar mobile memiliki kontrol buka/tutup, Escape, focus trap dan pembatasan fokus ke area aktif. CSS/JS admin dilayani sebagai aset lokal tanpa perubahan dependency atau kebutuhan build Vite.

| Area | Modul dan perilaku |
| --- | --- |
| Travel Catalog | CRUD paket, destinasi, fasilitas/included, itinerary, akomodasi dan pricing plans |
| Content | CRUD metadata halaman, heading section, FAQ, media dan pengaturan situs; relasi page_media dapat dipilih pada halaman dan placement/urutan yang sudah ada dipertahankan |
| Operations | Detail booking preview dan perubahan status draft/reviewed/ready; detail kontak dan status new/in_progress/resolved; riwayat chat read-only |
| Media | Upload gambar/video maksimal 2 MB, validasi MIME, nama file acak dari Laravel dan disk public. SVG hanya dapat dirujuk sebagai aset yang tersedia, tidak dapat diupload. File fisik yang sudah ada tidak otomatis dihapus saat record dihapus |
| Publik | Heading pada delapan view mengikuti section_key di SQL; metadata halaman, FAQ, paket, destinasi dan pengaturan menggunakan integrasi yang ada. Membuat record page bukan membuat template/rute publik baru secara otomatis |

### Keputusan dan keamanan

- Skema katalog SQL dipertahankan. Migrasi additive membuat tabel autentikasi/infrastruktur Laravel yang belum ada dan menambah `users.is_admin` dengan default false. Tidak ada data katalog yang dihapus.
- Gate admin berlaku untuk seluruh endpoint CMS. Akun biasa tidak dapat login sebagai admin. Kredensial admin pertama dibuat melalui command interaktif; tidak ada password admin default maupun promosi otomatis pengguna biasa.
- Registry resource berisi model/kolom allowlist. Request tidak dapat memilih tabel atau menulis atribut di luar field modul. Primary key tidak dapat diganti saat edit. Relasi gambar menerima media jenis image.
- Login dibatasi lima percobaan per kombinasi email/IP per menit dan 30 per IP. Session diregenerasi setelah login, diinvalidasi setelah logout; CSRF dan escaping Blade dipertahankan.
- Booking hanya dapat diubah statusnya; snapshot harga dan generated estimated_total tidak disentuh CMS. Chat tidak dapat diubah atau dihapus dari CMS. Delete yang ditolak foreign key menampilkan pesan yang dapat dipahami pengguna.
- Pengaturan menginvalidasi cache setelah penulisan. Label relasi pada daftar diambil secara batch, bukan per row.

### Menyiapkan akun pertama

Dari direktori project jalankan:

```powershell
php artisan admin:create email-admin@domain-anda.com --name="Nama Admin"
```

Command meminta password tersembunyi dan konfirmasinya. Password minimum 12 karakter dengan huruf besar/kecil, angka dan simbol. Email yang sudah ada ditolak; command tidak mengubah role akun yang sudah ada. Migrasi dan `storage:link` sudah diterapkan pada lingkungan lokal ini.

### Bukti verifikasi CMS

- TDD: uji akses/CRUD awal gagal sebelum implementasi; uji pricing, preservasi pivot, primary key, upload, filter status dan integrasi heading juga menunjukkan kegagalan sebelum perbaikan terkait.
- `php artisan test --compact`: **67 tes, 397 assertions, seluruhnya lulus**, termasuk 14 tes CMS.
- PHPUnit mencakup semua halaman modul, login/logout, user biasa, throttling, XSS, validasi relasi/path/upload, CRUD paket, proteksi foreign key, primary key khusus, cache, pagination, booking snapshot dan readonly chat.
- Browser memakai SQLite preview terpisah: login, dashboard, pembuatan destinasi/paket, navigasi sidebar dan pembatalan dialog hapus berhasil; tidak ada console warning/error pada pemeriksaan terakhir. Data preview tidak masuk database travel_hajj.
- Mobile 390 × 844: sidebar bekerja dan lebar dokumen tetap 390 px. Pemeriksaan mobile CMS ini tidak menggantikan audit mobile seluruh website.
- Pint dijalankan pada file PHP perubahan secara eksplisit: `--dirty` tidak tersedia karena direktori ini tidak menggunakan Git. Syntax JS diperiksa menggunakan `node --check public/js/admin.js`.

Referensi API: [Laravel filesystem](https://laravel.com/framework/docs/filesystem), [Laravel validation](https://laravel.com/framework/docs/validation), [Laravel views](https://laravel.com/framework/docs/views/forms). Batas lingkungan/deployment pada bagian sebelumnya tetap berlaku.

### Mode tanpa login sementara

Atas permintaan pengguna, `.env` lokal menggunakan `CMS_AUTH_ENABLED=false`. Bypass hanya berlaku ketika lingkungan `local`, IP client loopback, dan host localhost/127.0.0.1. Akses remote dan produksi tetap menggunakan autentikasi serta gate admin. CSRF dan validasi tetap aktif. `/admin/login` mengalihkan akses lokal ke `/admin`; sidebar menampilkan Mode tanpa login. Aktifkan kembali dengan `CMS_AUTH_ENABLED=true`, kemudian `php artisan config:clear`. Tes CMS terbaru: 16 tes, seluruhnya lulus, termasuk batas akses remote/produksi dan CRUD lokal dengan token CSRF valid.

### Upload gambar langsung pada modul CMS

Form paket dan destinasi kini memiliki `Upload gambar baru` dengan preview sebelum simpan dan preview gambar saat edit. Upload membuat record media_assets dan mengisi image_id secara otomatis. Form halaman mendukung maksimal lima gambar baru sekaligus dan menghubungkannya ke page_media. Upload gambar pada media library juga memiliki preview. Format JPG/PNG/GIF/WebP/AVIF dibatasi 2 MB per file, diperiksa isi/MIME-nya di server; SVG tidak dapat diupload. File baru dibersihkan jika penyimpanan data gagal; media lama dipertahankan karena dapat digunakan bersama. Pengujian terbaru: 18 tes CMS, 160 assertions, seluruhnya lulus. Preview file lokal berhasil diverifikasi di browser tanpa mengubah database utama.

### Midtrans BI SNAP: lingkungan pengujian

Pengguna memilih Sandbox. Konfigurasi lokal sudah menggunakan `MIDTRANS_IS_PRODUCTION=false`. Alur checkout yang ditemukan masih menggunakan Midtrans Snap biasa; keberadaan Client ID, Client Secret dan public key BI SNAP belum berarti checkout tersebut sudah terintegrasi dengan BI SNAP.

Implementasi transaksi BI SNAP memerlukan Partner ID, private key PKCS8 pasangan public key merchant, serta metode pembayaran yang diaktifkan untuk merchant. Private key disediakan melalui path file di server, tidak ditempelkan ke percakapan atau frontend. Public key merchant berbeda dari public key Midtrans yang digunakan untuk memverifikasi notifikasi. Tidak ada transaksi atau pemanggilan API pembayaran yang dijalankan pada pemeriksaan ini; integrasi BI SNAP belum dapat dinyatakan berfungsi sebelum konfigurasi tersebut tersedia dan pengujian Sandbox selesai.

Referensi: [Midtrans BI SNAP onboarding](https://docs.midtrans.com/reference/getting-started-1) dan [signature generation](https://docs.midtrans.com/reference/signature-generation).

### Alur pembayaran yang dapat dicoba secara lokal

`MIDTRANS_SIMULATION_ENABLED=true` di `.env` lokal mengaktifkan simulasi server. Default `.env.example` tetap false. Simulasi hanya tersedia pada lingkungan local, host/IP loopback, dan ketika mode Midtrans Production false. POST simulasi memerlukan session pemilik booking dan CSRF; throttling tetap aktif. Hasil disimpan sebagai provider `local_simulator`, status `simulated`, tanpa paid_at dan tanpa mengubah booking menjadi lunas. Pengulangan menghasilkan satu record per booking. Webhook Midtrans hanya boleh mengubah record provider midtrans.

Nominal berasal dari unit_price_snapshot booking, bukan input frontend atau harga paket yang dapat berubah setelah booking. Checkout tidak lagi menulis generated estimated_total. Pembuatan booking pada MySQL juga tidak menulis kolom tersebut; SQLite fixture tetap menyimpan nilai eksplisit. Status HTTP 200 dari endpoint status tidak dianggap bukti lunas; frontend memeriksa payment_status paid. Kegagalan gateway tidak dialihkan diam-diam menjadi simulasi berhasil.

Cara mencoba: buka /bookings, isi perjalanan, Review My Trip, centang konfirmasi review, Ready to Explore, pilih Payment Gateway (Online), Coba Simulasi Pembayaran, lalu Simulasikan Bayar Sekarang. Nomor VA/QR demo bukan instruksi pembayaran; jangan transfer dana. Hasil akhir menampilkan SIMULASI / BELUM DIBAYAR. Untuk mematikan simulasi, ubah MIDTRANS_SIMULATION_ENABLED=false dan jalankan php artisan config:clear.

Verifikasi: 84 tes / 491 assertions lulus, termasuk isolasi sesi, penolakan produksi/remote, nominal snapshot, pengulangan simulasi, serta penolakan webhook untuk record simulasi. Syntax JavaScript dan Pint lulus. Migrasi pembayaran sudah diterapkan. Browser localhost dengan database MySQL berhasil membuat booking contoh Pengujian Pembayaran Lokal dan menyimpan hasil simulasi; catatan contoh tersebut tetap tersedia untuk inspeksi. Integrasi BI SNAP ke API Midtrans belum selesai karena Partner ID, private key pasangan public key, dan pilihan metode merchant belum tersedia.

### Pola negara dan rotasi globe

Globe menggunakan asset lokal `public/assets/world-countries.geojson`, disederhanakan ke geometry dan nama dari data Natural Earth Admin 0 skala 1:110m (177 feature negara). Poligon buatan tangan diganti pola titik daratan dari mask geografis, dengan lubang poligon dipertahankan, titik yang lebih rapat, dan garis batas negara. Segmen belakang bola serta sambungan antimeridian tidak digambar melintasi globe. Batas bersifat kartografis skala global; pulau kecil tidak seluruhnya diwakili.

Follow Aircraft default nonaktif. Drag atau tombol panah mematikan follow, menghentikan auto-spin, dan mempertahankan yaw/pitch setelah dilepas. Follow dapat diaktifkan kembali secara eksplisit; Focus on aircraft memusatkan pesawat tanpa memaksa kamera mengikuti. Perpindahan mode dan perubahan route tidak mereset orientasi manual. Touch-action dan keyboard focus mendukung interaksi mobile/keyboard. Cesium eksternal yang menutupi renderer Canvas dihapus dari halaman ini; tidak ada ketergantungan baru.

Bug frame pertama turut diperbaiki: timestamp requestAnimationFrame yang lebih awal dari inisialisasi dapat membuat particleOffset negatif dan menghentikan render. Delta waktu sekarang dibatasi minimal nol. Tiga tes Node mencakup rotasi yang bertahan selama 600 frame, pemusatan eksplisit, dan timestamp awal; seluruhnya lulus. Pola negara diverifikasi pada browser localhost.

Verifikasi tambahan: seluruh empat tes JavaScript lulus, empat tes HomePage / 14 assertions lulus, dan sintaks JavaScript valid. Lebar komponen diperiksa pada viewport 320, 768, 1024 dan 1440 px; canvas mengikuti lebar stage. Checkbox Follow Aircraft terverifikasi nonaktif setelah rotasi keyboard. Bukti visual tersimpan pada globe-countries.png.

Sumber geografi: [Natural Earth Admin 0 Countries](https://www.naturalearthdata.com/downloads/110m-cultural-vectors/110m-admin-0-countries/) dan [GeoJSON sumber](https://github.com/nvkelso/natural-earth-vector/blob/master/geojson/ne_110m_admin_0_countries.geojson). Dataset publik ini disajikan dari server lokal setelah unduhan awal.
