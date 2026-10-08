# Database Travel

File: `travel_hajj.sql`. Target: MariaDB 10.4+ (XAMPP), MySQL 8.0.16+.

## Import
1. Jalankan MySQL di XAMPP.
2. Buka phpMyAdmin, pilih Import dan pilih SQL ini. Database `travel_hajj` dibuat otomatis.
3. Import sekali pada database baru. Tabel menggunakan IF NOT EXISTS; seed duplikat akan ditolak pada import ulang. Tidak ada DROP atau penghapusan tabel.

## Cakupan
- 8 halaman HTML, media gambar/video, judul section, FAQ.
- 6 paket katalog beserta destinasi, itinerary, fitur dan hotel.
- Pricing plans dalam IDR.
- Booking tiga tahap: draft, reviewed, ready. Harga disimpan sebagai snapshot; total dihitung database.
- Pesan kontak, sesi dan pesan chatbot (tabel kosong tanpa data pribadi contoh).
- Pengaturan kontak publik.

Path media relatif ke root website; file binary tetap di assets. API key tetap di server/.env.
Rating dan jumlah ulasan seed merupakan konten demo katalog, bukan rekaman ulasan pelanggan.

SQL ini belum menghubungkan HTML/JavaScript atau Express ke database. Form saat ini tetap berperilaku seperti sebelumnya.
Saat membuat backend gunakan prepared statements, validasi, kontrol akses untuk booking/chat, dan kebijakan retensi data pribadi.
Nilai kontak contoh harus diverifikasi sebelum website digunakan publik.
