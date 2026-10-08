# Product Requirements Document — Travel

Tanggal: 7 Oktober 2026  
Versi: 1.0  
Status: Acuan pengembangan berdasarkan berkas proyek saat ini

## 1. Ringkasan produk

Travel adalah website perjalanan untuk menemukan destinasi, membandingkan paket liburan, menyusun rencana booking, dan mendapatkan bantuan melalui chatbot. Produk menggunakan identitas visual biru, ilustrasi perjalanan, media lokal, serta animasi yang konsisten di setiap halaman.

Nama folder `travel_hajj` merupakan nama proyek teknis. Persyaratan khusus layanan haji atau umrah belum ditetapkan dan tidak termasuk dalam dokumen ini.

## 2. Tujuan dan pengguna

Tujuan produk:

- Memudahkan pengunjung menemukan paket sesuai destinasi dan anggaran.
- Menampilkan informasi perjalanan dan estimasi harga secara jelas.
- Menyediakan alur booking tiga langkah yang mudah digunakan di perangkat kecil.
- Menyediakan bantuan perjalanan melalui chatbot dan kanal kontak.
- Menyiapkan struktur database untuk katalog, konten, booking, dan komunikasi.

Pengguna utama adalah calon wisatawan individu, pasangan, dan keluarga. Pengelola dapat menggunakan struktur data sebagai dasar pengelolaan katalog, tetapi dashboard admin belum menjadi fitur yang tersedia.

## 3. Cakupan halaman

| Halaman | Berkas | Kebutuhan utama |
| --- | --- | --- |
| Home | `index.html` | Pengantar perjalanan, animasi scroll, paket populer, pricing, cara booking |
| Destinations | `destinations.html` | Kartu destinasi bergambar, pencarian, filter wilayah, penawaran |
| Packages | `packages.html` | Katalog, filter, detail paket, itinerary, akomodasi, estimasi biaya |
| About Us | `about.html` | Cerita produk, fitur, video latar, promosi pengalaman perjalanan, FAQ |
| Bookings | `bookings.html` | Pemilihan paket dan pengisian data perjalanan |
| Review your details | `booking-review.html` | Pemeriksaan data dan estimasi sebelum melanjutkan |
| Ready to explore | `booking-ready.html` | Ringkasan akhir dan demonstrasi pilihan pembayaran |
| Contact | `contact.html` | Kanal kontak, form pesan, jam layanan, chatbot |

## 4. Status implementasi

| Area | Kondisi saat ini | Kebutuhan berikutnya |
| --- | --- | --- |
| Antarmuka | Halaman HTML terpisah dengan CSS dan JavaScript | Menjaga konsistensi serta memverifikasi seluruh ukuran layar |
| Katalog | Data paket tersedia di JavaScript dan seed SQL | Menghubungkan pembacaan katalog ke database |
| Booking | Draft disimpan di `sessionStorage` | Menyimpan dan memvalidasi booking di server |
| Pembayaran | Pilihan tunai dan simulasi gateway | Integrasi penyedia pembayaran jika masuk ruang lingkup rilis |
| Invoice | Tombol demonstrasi menampilkan notifikasi | Membuat dokumen nyata dari booking tersimpan |
| Form kontak | Validasi dan tampilan sukses lokal | Penyimpanan pesan dan pengiriman melalui layanan yang dipilih |
| Chatbot | Endpoint Express tersedia; konfigurasi saat ini menggunakan Groq | Konfigurasi kredensial dan verifikasi respons provider secara langsung |
| Database | SQL dengan tabel, komentar, relasi, dan seed tersedia | Verifikasi import dan implementasi koneksi aplikasi |

Tampilan sukses simulasi tidak boleh dianggap sebagai pembayaran terverifikasi, reservasi tersimpan, atau pesan yang benar-benar terkirim.

## 5. Persyaratan fungsional

### FR-01 — Navigasi

- Semua halaman menggunakan identitas, menu utama, dan tautan yang konsisten.
- Menu mencakup Home, Packages, Destinations, Bookings, About Us, dan Contact.
- Layar kecil menyediakan tombol burger dengan status `aria-expanded` yang sesuai.
- Halaman aktif ditandai; navigasi dapat digunakan melalui keyboard.

### FR-02 — Home dan motion

- Home menampilkan pengalaman perjalanan berbasis urutan gambar lokal di `assets/journey`.
- Kemajuan scroll menggerakkan frame dan pergantian konten secara terarah.
- Pengguna dapat melewati bagian animasi untuk menuju konten utama.
- Pemuatan frame dibatasi agar tidak mengunduh seluruh urutan sekaligus.
- Preferensi reduced motion menyediakan pengalaman yang lebih tenang untuk animasi scroll.

### FR-03 — Destinasi

- Kartu menampilkan gambar, nama destinasi, lokasi, harga atau penawaran, dan rating katalog.
- Pengunjung dapat mencari destinasi dan memfilter wilayah.
- Animasi masuk dan floating dipicu saat kartu terlihat; animasi tidak perlu berjalan saat kartu di luar layar.
- Rating dan jumlah ulasan demo tidak dipresentasikan sebagai ulasan pelanggan yang terverifikasi.

### FR-04 — Paket perjalanan

- Katalog memuat Greece, Maldives, Canada, Japan, Bali, dan Switzerland berdasarkan data proyek.
- Detail paket mencakup durasi, harga IDR, fasilitas, itinerary, dan akomodasi.
- Filter, preview detail, wishlist lokal, dan kalkulasi biaya memberikan umpan balik yang jelas.
- Paket yang dipilih untuk booking harus cocok dengan identitas paket dan harga pada sumber data utama.

### FR-05 — About Us

- Halaman menampilkan cerita produk, manfaat perjalanan, fitur, dan FAQ.
- Video lokal `assets/about/holiday-background.mp4` menjadi latar visual utama.
- Video menggunakan autoplay, muted, dan loop tanpa tombol pause, sesuai permintaan desain saat ini.
- Media utama ditampilkan utuh tanpa crop; rasio gambar atau video dipertahankan.
- Poster tersedia sebagai fallback ketika video belum dapat diputar.
- Teks tetap terbaca dan tidak bergantung pada video untuk menyampaikan informasi penting.

### FR-06 — Booking tiga langkah

1. **Choose your trip:** pilih paket, tanggal, jumlah wisatawan, kota asal, nama, email, dan catatan.
2. **Review your details:** tampilkan data yang sama, estimasi total, opsi edit, dan persetujuan pemeriksaan.
3. **Ready to explore:** tampilkan ringkasan akhir serta pilihan pembayaran tunai atau simulasi gateway.

Ketentuan:

- Tanggal keberangkatan tidak boleh sebelum hari ini.
- Jumlah wisatawan mengikuti batas antarmuka saat ini, yaitu 1–6 orang dewasa.
- Nama dan email wajib valid sebelum melanjutkan.
- Total ditampilkan dalam IDR dan dihitung dari harga paket serta jumlah wisatawan.
- Draft tetap tersedia selama sesi tab dan dapat dihapus oleh pengguna.
- Akses langkah lanjutan tanpa draft valid harus memberikan arahan kembali ke langkah awal.
- Setelah integrasi database, server menghitung harga dari katalog dan menyimpan snapshot harga; nilai kiriman browser tidak menjadi sumber kebenaran.
- Kode reservasi permanen harus dibuat server dan unik. Kode acak demonstrasi saat ini belum menjadi bukti reservasi.

### FR-07 — Pembayaran

- Demonstrasi mendukung pilihan tunai serta tampilan QRIS, virtual account, dan kartu kredit.
- Mode simulasi harus diberi label yang mudah terlihat.
- Jika pembayaran nyata dikembangkan, status pembayaran hanya berubah setelah verifikasi server terhadap penyedia pembayaran.
- Kredensial gateway dan data kartu sensitif tidak disimpan di frontend atau tabel aplikasi.
- Database saat ini belum memiliki model transaksi pembayaran lengkap; pengembangan pembayaran nyata memerlukan migrasi tambahan.

### FR-08 — Kontak

- Form menyediakan nama, email, telepon opsional, topik, kanal balasan, dan pesan.
- Validasi memberikan pesan kesalahan dekat field terkait.
- Batas pesan mengikuti antarmuka, yaitu 600 karakter.
- Setelah backend terhubung, status berhasil hanya ditampilkan setelah server menerima pesan.
- Nomor telepon, WhatsApp, email, alamat, dan jam operasional contoh harus diganti atau diverifikasi sebelum rilis publik.

### FR-09 — Live chatbot

- Pengunjung dapat mengirim pertanyaan perjalanan dan menerima jawaban melalui `POST /api/chat`.
- Antarmuka menampilkan status menunggu, kesalahan, dan kesempatan mencoba kembali.
- Konfigurasi kode saat ini menggunakan Groq melalui backend; provider dan model dapat berubah melalui konfigurasi.
- API key disimpan di `server/.env` dan tidak masuk HTML, JavaScript publik, SQL, atau dokumentasi.
- Endpoint membatasi panjang pesan, jumlah riwayat, frekuensi permintaan, dan waktu tunggu provider.
- Jawaban dirender sebagai teks aman.
- Chatbot tidak boleh mengklaim telah melakukan booking atau pembayaran tanpa hasil transaksi backend.
- Website harus dijalankan melalui server yang menyediakan endpoint chat, atau proxy yang dikonfigurasi; server HTML statis saja tidak menyediakan API tersebut.

## 6. Persyaratan data

Sumber skema: `database/travel_hajj.sql`. Petunjuk import: `database/README.md`.

Target database adalah MariaDB 10.4+ pada XAMPP atau MySQL 8.0.16+, menggunakan InnoDB dan UTF-8.

| Kelompok | Tabel | Tujuan |
| --- | --- | --- |
| Konten dan media | `media_assets`, `pages`, `page_media`, `page_sections`, `faqs`, `site_settings` | Menyimpan metadata halaman, path media, konten, FAQ, dan konfigurasi publik |
| Katalog | `destinations`, `packages`, `package_items`, `package_itineraries`, `package_accommodations`, `pricing_plans` | Menyimpan destinasi, paket, fasilitas, itinerary, hotel, dan tier harga |
| Booking | `bookings` | Menyimpan data perjalanan, kontak pemesan, snapshot harga, dan status preview |
| Komunikasi | `contact_messages`, `chat_sessions`, `chat_messages` | Menyimpan pesan kontak serta sesi dan riwayat chatbot |

- Gambar dan video tetap berupa berkas aset; database menyimpan path, jenis media, dan metadata.
- Seed katalog tidak memuat data pribadi pengguna nyata atau API key.
- Skema menggunakan relasi dan indeks untuk menjaga konsistensi data.
- SQL saat ini menggunakan pembuatan tabel tanpa penghapusan; seed ditujukan untuk import sekali pada database baru.
- Penyimpanan data pribadi memerlukan kebijakan akses, retensi, dan penghapusan sebelum diaktifkan untuk publik.
- Endpoint booking, kontak, dan katalog berbasis database merupakan pekerjaan lanjutan; keberadaan SQL belum berarti aplikasi telah menggunakannya.

## 7. Persyaratan nonfungsional

### Tampilan dan aksesibilitas

- HTML menggunakan landmark semantik, hierarki heading, label form, serta alt text yang sesuai.
- Layout nyaman pada lebar 360 px, 768 px, 1024 px, dan 1440 px tanpa overflow horizontal.
- Komponen interaktif memiliki focus state, target sentuh yang nyaman, dan kontras teks yang memadai.
- Modal mendukung keyboard, pengelolaan fokus, dan pengembalian fokus ke pemicu.
- Animasi dekoratif menghormati reduced motion, dengan perilaku video About yang secara khusus mengikuti permintaan autoplay terus-menerus saat ini.
- Tailwind CSS dan Font Awesome tetap konsisten dengan desain proyek.

### Performa dan keandalan

- Gambar di bawah lipatan dimuat secara lazy jika sesuai.
- Media menyediakan dimensi atau rasio agar layout stabil selama pemuatan.
- Animasi scroll tidak memicu pekerjaan berat berulang pada setiap event.
- Kegagalan API tidak menghapus input atau draft pengguna.
- Build produksi perlu menentukan strategi aset dan CSS; konfigurasi CDN browser saat ini adalah implementasi yang tersedia.

### Keamanan

- Validasi server wajib untuk data booking, kontak, dan chat setelah integrasi.
- Query database menggunakan parameter atau prepared statements.
- Data booking dan riwayat chat tidak dapat diakses hanya dengan menebak ID.
- Rahasia server serta dump SQL tidak disajikan sebagai aset publik pada deployment produksi.
- Log tidak memuat API key atau data pribadi yang tidak diperlukan.

## 8. Kriteria penerimaan

- Seluruh delapan halaman dapat dibuka dan tautan navigasinya menuju halaman yang benar.
- Burger menu dapat dibuka, ditutup, dan digunakan melalui keyboard pada layar kecil.
- Home, kartu destinasi, serta video About mengikuti perilaku motion dan media yang ditentukan.
- Pencarian dan filter menampilkan hasil yang benar serta keadaan kosong yang jelas.
- Data booking konsisten dari langkah pertama sampai ringkasan akhir; edit memperbarui estimasi.
- Pembayaran demonstrasi jelas berlabel simulasi dan tidak menghasilkan klaim pembayaran nyata.
- Chatbot memberikan respons provider nyata setelah konfigurasi, serta menangani timeout dan respons gagal dengan baik.
- Import SQL pada database baru berhasil dan seed memiliki relasi yang valid.
- Setelah integrasi, booking dan pesan kontak dapat disimpan serta dibaca kembali sesuai kontrol akses.
- Tidak terdapat secret dalam berkas publik, respons API, atau commit.

Kriteria tersebut adalah target verifikasi. Dokumen ini tidak menyatakan seluruh pengujian sudah berhasil; import SQL dan respons provider nyata memerlukan pengujian pada lingkungan yang berjalan.

## 9. Prioritas pengembangan

| Prioritas | Pekerjaan | Hasil yang diharapkan |
| --- | --- | --- |
| P0 | Verifikasi konfigurasi chatbot dan jalankan pengujian provider | Chat bantuan berfungsi dengan respons nyata |
| P0 | Audit konsistensi alur booking dan label simulasi | Pengguna memahami estimasi dan status reservasi |
| P1 | Import database, koneksi server, dan API katalog | Satu sumber data paket dan harga |
| P1 | Penyimpanan booking dan kontak dengan validasi server | Data bertahan di luar sesi browser |
| P1 | Verifikasi kontak, aksesibilitas, dan responsivitas | Website siap digunakan pengunjung |
| P2 | Integrasi pembayaran dan invoice nyata jika disetujui sebagai fase berikutnya | Transaksi terverifikasi dan dokumen reservasi |
| P2 | Dashboard pengelola dan autentikasi jika diperlukan | Pengelolaan katalog serta layanan operasional |

## 10. Di luar cakupan saat ini

- Aplikasi Android atau iOS native dan distribusi app store.
- Integrasi inventori maskapai atau hotel secara langsung.
- Pemrosesan pembayaran nyata, refund otomatis, dan penerbitan tiket.
- Akun pengguna, dashboard admin, program loyalitas, serta ulasan terverifikasi.
- Produk khusus haji atau umrah sampai kebutuhan bisnisnya ditetapkan.

## 11. Metrik dan keputusan terbuka

Metrik yang dapat diukur setelah instrumentasi tersedia: jumlah kunjungan katalog, klik mulai booking, rasio penyelesaian review, jumlah pesan kontak yang diterima server, serta tingkat keberhasilan respons chatbot. Target numerik belum ditetapkan dan perlu disesuaikan dengan baseline penggunaan.

Keputusan terbuka: pemilik layanan dan kontak operasional, lingkungan deployment, kredensial provider AI, aturan retensi data, biaya tambahan booking, penyedia pembayaran, dan kebutuhan dashboard pengelola. Dokumen ini tidak menetapkan jadwal atau anggaran yang belum disepakati.
