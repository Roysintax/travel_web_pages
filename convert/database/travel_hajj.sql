-- Database Travel: MySQL 8.0.16+ / MariaDB 10.4+ (XAMPP).
-- Import melalui phpMyAdmin > Import, atau mysql -u root < database/travel_hajj.sql.
-- Ini skema + data katalog dari HTML/JS. Belum menghubungkan frontend ke database.
-- Non-destruktif: tidak ada DROP/TRUNCATE. Import kedua kali akan menolak seed duplikat.
-- Semua nominal mengikuti katalog terbaru: Rupiah (IDR), bukan USD.
CREATE DATABASE IF NOT EXISTS travel_hajj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE travel_hajj;

-- Media: simpan path gambar/video, BUKAN file binary atau base64.
-- Contoh gambar: assets/greece.png. Contoh video: assets/about/holiday-background.mp4.
-- File aslinya tetap berada di folder assets; path relatif terhadap root website.
CREATE TABLE IF NOT EXISTS media_assets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 file_path VARCHAR(255) NOT NULL UNIQUE COMMENT 'Path gambar, video atau frame animasi',
 media_type ENUM('image','video') NOT NULL,
 alt_text VARCHAR(500) NULL COMMENT 'Teks alternatif gambar; video dekoratif boleh kosong',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Aset visual seluruh halaman';

-- Halaman Home, Destinations, Packages, About Us, Contact dan 3 tahap Booking.
CREATE TABLE IF NOT EXISTS pages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(80) NOT NULL UNIQUE,
 html_file VARCHAR(100) NOT NULL UNIQUE,
 title VARCHAR(255) NOT NULL,
 meta_description TEXT NULL
) ENGINE=InnoDB COMMENT='Identitas halaman HTML';

-- Satu halaman dapat memiliki banyak gambar/video (hero, kartu, ilustrasi, poster).
CREATE TABLE IF NOT EXISTS page_media (
 page_id BIGINT UNSIGNED NOT NULL,
 media_id BIGINT UNSIGNED NOT NULL,
 placement VARCHAR(80) NOT NULL DEFAULT 'content',
 sort_order INT UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY(page_id,media_id),
 FOREIGN KEY(page_id) REFERENCES pages(id) ON DELETE CASCADE,
 FOREIGN KEY(media_id) REFERENCES media_assets(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Relasi halaman dengan gambar/video';

CREATE TABLE IF NOT EXISTS destinations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(100) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 region VARCHAR(80) NOT NULL COMMENT 'Filter Asia, Europe, North America',
 image_id BIGINT UNSIGNED NULL,
 FOREIGN KEY(image_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Katalog destinasi';

-- Harga awal/original serta rating ini merupakan data demo dari katalog frontend.
CREATE TABLE IF NOT EXISTS packages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(100) NOT NULL UNIQUE,
 booking_key VARCHAR(40) NULL UNIQUE COMMENT 'Alias booking-flow.js: greece, maldives, canada, japan',
 destination_id BIGINT UNSIGNED NOT NULL,
 image_id BIGINT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NOT NULL,
 category VARCHAR(40) NOT NULL,
 badge VARCHAR(80) NULL,
 days SMALLINT UNSIGNED NOT NULL,
 nights SMALLINT UNSIGNED NOT NULL,
 price_per_person DECIMAL(15,2) NOT NULL,
 original_price DECIMAL(15,2) NULL,
 currency CHAR(3) NOT NULL DEFAULT 'IDR',
 rating DECIMAL(2,1) NULL,
 review_count INT UNSIGNED NOT NULL DEFAULT 0,
 is_luxury BOOLEAN NOT NULL DEFAULT FALSE,
 is_active BOOLEAN NOT NULL DEFAULT TRUE,
 CHECK(days > 0 AND nights < days),
 CHECK(price_per_person >= 0),
 CHECK(rating IS NULL OR (rating >= 0 AND rating <= 5)),
 FOREIGN KEY(destination_id) REFERENCES destinations(id),
 FOREIGN KEY(image_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Paket perjalanan dari js/packages.js';

CREATE TABLE IF NOT EXISTS package_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 package_id BIGINT UNSIGNED NOT NULL,
 item_type ENUM('feature','included') NOT NULL,
 description TEXT NOT NULL,
 sort_order INT UNSIGNED NOT NULL,
 FOREIGN KEY(package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Fitur kartu dan layanan yang termasuk paket';

CREATE TABLE IF NOT EXISTS package_itineraries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 package_id BIGINT UNSIGNED NOT NULL,
 day_number SMALLINT UNSIGNED NOT NULL,
 title VARCHAR(255) NOT NULL,
 description TEXT NOT NULL,
 UNIQUE(package_id,day_number),
 FOREIGN KEY(package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Jadwal perjalanan per hari';

CREATE TABLE IF NOT EXISTS package_accommodations (
 package_id BIGINT UNSIGNED PRIMARY KEY,
 name VARCHAR(255) NOT NULL,
 star_label VARCHAR(80) NULL,
 perks TEXT NULL,
 FOREIGN KEY(package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Hotel yang ditampilkan di detail paket';

CREATE TABLE IF NOT EXISTS pricing_plans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 price_per_person DECIMAL(15,2) NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'IDR',
 CHECK(price_per_person >= 0)
) ENGINE=InnoDB COMMENT='Tier harga halaman Packages; bukan biaya tambahan otomatis';

-- Booking masih preview. Draft/reviewed/ready mengikuti 3 halaman, bukan status pembayaran.
-- Simpan snapshot harga saat rencana dibuat agar perubahan katalog tidak mengubah total lama.
-- Jangan simpan detail kartu kredit. Tidak ada tabel pembayaran karena belum ada payment gateway.
CREATE TABLE IF NOT EXISTS bookings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference_code VARCHAR(40) NOT NULL UNIQUE COMMENT 'Generate secara aman di backend',
 package_id BIGINT UNSIGNED NOT NULL,
 departure_date DATE NOT NULL,
 departure_city VARCHAR(100) NOT NULL,
 travelers TINYINT UNSIGNED NOT NULL,
 lead_name VARCHAR(100) NOT NULL,
 lead_email VARCHAR(254) NOT NULL,
 notes VARCHAR(500) NULL,
 unit_price_snapshot DECIMAL(15,2) NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'IDR',
 estimated_total DECIMAL(17,2) GENERATED ALWAYS AS (unit_price_snapshot * travelers) STORED,
 preview_status ENUM('draft','reviewed','ready') NOT NULL DEFAULT 'draft',
 reviewed_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK(travelers BETWEEN 1 AND 6),
 CHECK(unit_price_snapshot >= 0),
 INDEX(preview_status,created_at),
 FOREIGN KEY(package_id) REFERENCES packages(id)
) ENGINE=InnoDB COMMENT='Data 3 tahap booking; perlu kontrol akses backend untuk data pribadi';

CREATE TABLE IF NOT EXISTS contact_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 email VARCHAR(254) NOT NULL,
 phone VARCHAR(40) NULL,
 topic VARCHAR(100) NOT NULL,
 reply_channel ENUM('Email','WhatsApp','Phone call') NOT NULL DEFAULT 'Email',
 message VARCHAR(600) NOT NULL,
 status ENUM('new','in_progress','resolved') NOT NULL DEFAULT 'new',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(status,created_at)
) ENGINE=InnoDB COMMENT='Form Contact; tidak berarti email otomatis terkirim';

CREATE TABLE IF NOT EXISTS chat_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 public_token CHAR(64) NOT NULL UNIQUE COMMENT 'Token acak; jangan gunakan ID urut untuk akses publik',
 model VARCHAR(150) NOT NULL DEFAULT 'Prism-ML/Ternary-Bonsai-27B',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NOT NULL COMMENT 'Batas retensi percakapan; hapus sesuai kebijakan'
) ENGINE=InnoDB COMMENT='Sesi chatbot; API key tetap di server/.env, bukan di SQL';

CREATE TABLE IF NOT EXISTS chat_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 session_id BIGINT UNSIGNED NOT NULL,
 role ENUM('user','assistant') NOT NULL,
 content TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(session_id,id),
 FOREIGN KEY(session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Pesan chat; error/typing UI tidak disimpan sebagai jawaban AI';

CREATE TABLE IF NOT EXISTS faqs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 page_id BIGINT UNSIGNED NOT NULL,
 question VARCHAR(500) NOT NULL,
 answer TEXT NOT NULL,
 sort_order INT UNSIGNED NOT NULL,
 FOREIGN KEY(page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Accordion FAQ About, Packages, Contact';

CREATE TABLE IF NOT EXISTS page_sections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 page_id BIGINT UNSIGNED NOT NULL,
 section_key VARCHAR(100) NOT NULL,
 heading VARCHAR(500) NULL,
 UNIQUE(page_id,section_key),
 FOREIGN KEY(page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Judul section dari HTML; struktur layout tetap di HTML/CSS';

CREATE TABLE IF NOT EXISTS site_settings (
 setting_key VARCHAR(100) PRIMARY KEY,
 setting_value TEXT NOT NULL,
 description VARCHAR(255) NULL
) ENGINE=InnoDB COMMENT='Kontak, jam operasional dan informasi publik; jangan masukkan secret';

-- Data seed di bawah berasal dari file HTML dan katalog JS pada saat SQL dibuat.
-- Tidak ada contoh booking/pesan dengan data pribadi pengguna.
START TRANSACTION;

-- Gambar/video aktual yang direferensikan HTML dan katalog paket.
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (1,'assets/about/holiday-background.mp4','video',NULL);
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (2,'assets/about/destinations.jpg','image','A traveler in a sun hat overlooking a tropical beach');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (3,'assets/about/planning.jpg','image','Passport, map, and camera with an airplane above a tropical coast');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (4,'assets/about/booking.jpg','image','An infinity pool and sun loungers overlooking a blue bay');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (5,'assets/about/mobile.jpg','image','A hand holding a phone with a travel discovery screen beside a sunny coastal village');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (6,'assets/about/holiday-full.png','image',NULL);
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (7,'assets/greece.png','image','');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (8,'assets/maldives.png','image','');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (9,'assets/canada.png','image','');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (10,'assets/japan.png','image','');
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (11,'gemini_generated_video_c9ca0f42.mp4','video',NULL);
INSERT INTO media_assets (id,file_path,media_type,alt_text) VALUES (12,'assets/journey/ezgif-frame-001.jpg','image','A traveler looking through an airplane window toward the sunset');
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (1,'about','about.html','About Us — Travel Around the World','Meet Travel: a brighter way to discover destinations and bring your next holiday together.');
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (1,4,'content',1);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (1,2,'content',2);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (1,1,'content',3);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (1,6,'content',4);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (1,5,'content',5);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (1,3,'content',6);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (1,'What is Travel?','Travel is a place to discover destinations and explore holiday packages, with a cinematic introduction that brings the journey to life.',1);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (1,'How can I find a destination that suits me?','Visit our Destinations page to search by country or destination and filter by region. Each card includes a starting price to help you compare the options shown.',2);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (1,'Can I book a trip directly on this website?','The current booking form is a preview. You can enter trip preferences and explore packages, but it does not make reservations or collect payment.',3);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (1,'Does Travel work on mobile?','Yes. The website adapts to phones, tablets, and desktops. You can explore directly in your browser without downloading an app.',4);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (1,'Can I skip the cinematic introduction?','Yes. Select “Skip journey” on Home to go straight to the travel content. The introduction also respects your device’s reduced motion setting.',5);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (1,'Where should I start?','Start with Destinations for inspiration, or browse the packages on Home to compare featured trips and their durations.',6);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (1,'section-1','About Travel — Holiday made simple, smart and connected');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (1,'section-2','Your entire journey. A little more connected.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (1,'section-3','Thoughtful travel. Unforgettable holidays.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (1,'section-4','A world of possibility. Wherever you are.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (1,'section-5','A little clarity. A better journey.');
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (2,'booking-ready','booking-ready.html','Ready to Explore — Travel','Plan your next Travel getaway, choose a destination, and review your trip estimate.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'section-1','Your next adventure awaits.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'flow-empty','Your journey starts with a plan');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'section-3',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'section-4',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'flow-notes-section',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'section-6',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (2,'section-7',NULL);
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (3,'booking-review','booking-review.html','Review Your Details — Travel','Plan your next Travel getaway, choose a destination, and review your trip estimate.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (3,'section-1','Let’s get the details right.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (3,'flow-empty','Your journey starts with a plan');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (3,'section-3',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (3,'section-4',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (3,'flow-notes-section',NULL);
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (4,'bookings','bookings.html','Bookings — Travel Around the World','Plan your next Travel getaway, choose a destination, and review your trip estimate.');
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (4,9,'content',1);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (4,7,'content',2);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (4,10,'content',3);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (4,8,'content',4);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (4,'section-1','Your next chapter starts here.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (4,'section-2','Where are we heading?');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (4,'section-3','Make the journey yours');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (4,'section-4','Who’s leading the adventure?');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (4,'section-5','Thoughtfully curated');
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (5,'contact','contact.html','Contact Us — Travel Around the World','Contact Travel: call, email, visit our office, or chat live with a travel expert to plan your next holiday.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (5,'section-1','Let’s plan it together.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (5,'section-2','Ways to reach us');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (5,'section-3','Tell us about your trip');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (5,'office','Drop by for a coffee & a plan.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (5,'section-5','Find a place that feels like you.');
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (6,'destinations','destinations.html','Destinations — Travel Around the World',NULL);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (6,9,'content',1);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (6,7,'content',2);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (6,10,'content',3);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (6,8,'content',4);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (6,11,'content',5);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (6,'section-1','Find your next great escape.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (6,'explore','Where will you go?');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (6,'section-3','Top Trips This Week');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (6,'section-4','A little more peace of mind');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (6,'section-5','Your dream trip starts here.');
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (7,'index','index.html','Travel — Around the World',NULL);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (7,9,'content',1);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (7,7,'content',2);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (7,10,'content',3);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (7,12,'content',4);
INSERT INTO page_media (page_id,media_id,placement,sort_order) VALUES (7,8,'content',5);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'home','Every journey starts with a view.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'booking',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'section-3','Why travel with us');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'packages','POPULAR PACKAGES');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'pricing','PRICING DETAILS');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'how-to-book','HOW TO BOOK');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (7,'about','Our travelers and their experiences');
INSERT INTO pages (id,slug,html_file,title,meta_description) VALUES (8,'packages','packages.html','Travel Packages — Travel Around the World','Explore all-inclusive holiday packages by Travel: island escapes, cultural journeys, and mountain adventures with verified stays and 24/7 support.');
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (8,'What is included in the package prices?','Standard and Luxury packages include round-trip flights or transfers, verified 4–5 star accommodations, daily breakfast, airport VIP pick-up, and featured excursions with certified English/Indonesian-speaking guides.',1);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (8,'Can I customize dates or extend our stay?','Yes! Every package can be extended or tailored. Use the Custom Estimator above, or contact our team via Live Chat / WhatsApp to adjust dates, room types, or add special excursions.',2);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (8,'What is the cancellation and rescheduling policy?','We offer 100% free rescheduling up to 30 days prior to departure for all standard packages. Cancellations made between 30 to 14 days are subject to airline voucher terms with no penalty on hotel reservations.',3);
INSERT INTO faqs (page_id,question,answer,sort_order) VALUES (8,'Do you assist with visa applications?','Yes. Our dedicated visa assistance team provides document checklists, appointment bookings, and embassy submission support for Schengen (Greece), Japan, and Canadian tourist visas.',4);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'section-1','Tailored packages. Unforgettable journeys.');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'explore-packages','Featured Travel Packages');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'section-3',NULL);
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'pricing-plans','Choose Your Travel Tier');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'section-5','Build & Estimate Your Package');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'section-6','Memories Created with Us');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'section-7','Packages FAQ');
INSERT INTO page_sections (page_id,section_key,heading) VALUES (8,'section-8','Let our AI or travel experts craft it for you.');
-- Six packages: données demo persis katalog js/packages.js, bukan ketersediaan real-time.
INSERT INTO destinations (id,slug,name,region,image_id) VALUES (1,'greece-islands','Santorini & Mykonos, Greece','Europe',7);
INSERT INTO packages (id,slug,booking_key,destination_id,image_id,title,description,category,badge,days,nights,price_per_person,original_price,currency,rating,review_count,is_luxury) VALUES (1,'greece-islands','greece',1,7,'Greek Islands Escape','Wander whitewashed alleys, savor private sunset catamaran cruises, and unwind in cliffside boutique cave suites.','beach','Best Seller',6,5,19500000,22500000,'IDR',4.8,142,0);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'feature','Flights Included',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'feature','Caldera Suites',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'feature','Catamaran Cruise',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'feature','Breakfast Included',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'included','Round-trip international & domestic flight connections',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'included','5 nights luxury 4â€“5 star boutique cave & beach suites',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'included','Daily gourmet Aegean breakfast',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'included','Private sunset catamaran excursion with BBQ lunch',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (1,'included','All airport & ferry VIP transfers',5);
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (1,1,'Arrival in Athens & Ferry to Santorini','VIP meet & greet at Athens airport, scenic catamaran ferry to Santorini, check-in to your cliffside suite with welcome wine.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (1,2,'Oia Village Walking Tour & Caldera Sunset','Guided stroll through iconic blue-domed churches, photography stops, and reserved terrace dining during sunset.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (1,3,'Private Catamaran & Volcanic Hot Springs','Full-day sailing around the caldera, swimming in geothermal springs, with Greek barbecue lunch onboard.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (1,4,'Speedboat to Mykonos & Windmills Exploration','Transfer to Mykonos, explore Little Venice, traditional windmills, and vibrant waterfront cafes.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (1,5,'Delos Archaeological Island & Beach Club','Morning historical boat tour to UNESCO-listed Delos ruins, afternoon leisure at Psarou beach.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (1,6,'Souvenir Stroll & Flight Home','Last Greek breakfast, private airport transfer, and departure flight with cherished memories.');
INSERT INTO package_accommodations (package_id,name,star_label,perks) VALUES (1,'Canaves Oia Suites & Mykonos Grand Hotel','5-Star Luxury','Infinity pool overlooking the volcano caldera, private jacuzzi, and complimentary Champagne.');
INSERT INTO destinations (id,slug,name,region,image_id) VALUES (2,'maldives-paradise','North MalÃ© Atoll, Maldives','Asia',8);
INSERT INTO packages (id,slug,booking_key,destination_id,image_id,title,description,category,badge,days,nights,price_per_person,original_price,currency,rating,review_count,is_luxury) VALUES (2,'maldives-paradise','maldives',2,8,'Maldives Overwater Paradise','Unrivaled tropical seclusion with glass-floor water villas, manta ray reef snorkeling, and floating breakfasts.','beach','Luxury Escape',5,4,24500000,28500000,'IDR',4.9,188,1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'feature','Speedboat Transfer',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'feature','Overwater Villa',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'feature','All-Inclusive Dine',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'feature','Coral Snorkel',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'included','4 nights in an Ocean Pool Overwater Villa',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'included','All-inclusive gourmet dining & premium beverages',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'included','Round-trip airport speedboat transfer',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'included','Guided coral reef snorkeling gear and excursion',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (2,'included','Sunset champagne dolphin safari',5);
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (2,1,'Speedboat Arrival & Overwater Villa Check-In','30-minute luxury speedboat transfer from Velana Airport directly to your private villa over the turquoise lagoon.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (2,2,'Guided Coral Reef & Turtle Snorkeling Safari','Marine biologist-guided safari discovering sea turtles, nurse sharks, and vibrant coral formations.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (2,3,'Sunset Dolphin Cruise & Floating Breakfast','Morning floating breakfast in your private plunge pool followed by an evening champagne dolphin cruise.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (2,4,'Overwater Spa Treatment & Candlelit Beach Dinner','60-minute revitalizing massage with ocean-view floor glass, ending with private beach barbecue.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (2,5,'Island Souvenirs & Scenic Departure','Morning paddleboarding, farewell lunch, and transfer to MalÃ© for your flight home.');
INSERT INTO package_accommodations (package_id,name,star_label,perks) VALUES (2,'Sun Siyam Olhuveli & Baros Maldives','5-Star Premium','Direct lagoon ladder access, private sundeck, glass floor panel, 24/7 villa host.');
INSERT INTO destinations (id,slug,name,region,image_id) VALUES (3,'japan-discovery','Kyoto, Tokyo & Nara, Japan','Asia',10);
INSERT INTO packages (id,slug,booking_key,destination_id,image_id,title,description,category,badge,days,nights,price_per_person,original_price,currency,rating,review_count,is_luxury) VALUES (3,'japan-discovery','japan',3,10,'Japan Imperial & Cultural Odyssey','Ancient shrines, bullet train travel, serene bamboo groves, traditional Ryokan onsen, and neon Tokyo city lights.','culture','Trending',8,7,33500000,38000000,'IDR',4.9,215,0);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'feature','Shinkansen Pass',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'feature','Onsen Ryokan',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'feature','Kimono Experience',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'feature','Tea Ceremony',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'included','7 nights accommodation including 1 night luxury onsen ryokan',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'included','7-day JR Nationwide Shinkansen Rail Pass',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'included','Daily breakfast & multi-course traditional Kaiseki dinner',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'included','Private certified English-speaking local guides',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (3,'included','Authentic Kimono dressing & Uji tea ceremony',5);
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,1,'Konnichiwa Tokyo: Shinjuku & Shibuya','Airport limousine transfer, check-in in Shinjuku, evening crossing at Shibuya Sky observation deck.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,2,'Historic Asakusa & Modern Akihabara','Senso-ji temple morning ritual, traditional Nakamise treats, and afternoon tech & anime culture.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,3,'Bullet Train to Hakone & Mt. Fuji Onsen','Ride the Tokaido Shinkansen to Hakone, cruise Lake Ashi, stay in a hot spring Ryokan with Kaiseki banquet.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,4,'Scenic Train to Kyoto & Gion Geisha District','Arrive in ancient capital Kyoto, evening lantern walking tour through historic Gion alleyways.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,5,'Fushimi Inari Torii Gates & Arashiyama Bamboo','Hike through 10,000 vermillion torii gates and stroll the whispering Arashiyama bamboo forest.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,6,'Nara Deer Park & Kinkaku-ji Golden Pavilion','Feed free-roaming sacred deer in Nara, then admire the gold leaf reflections at Kinkaku-ji.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,7,'Authentic Tea Ceremony & Dotonbori Osaka Food Tour','Zen matcha ceremony followed by evening culinary adventure in Osakaâ€™s lively street food hub.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (3,8,'Souvenir Hunting & Kansai Departure','Pick up authentic matcha, ceramics, and sweets before private Kansai/Haneda airport transfer.');
INSERT INTO package_accommodations (package_id,name,star_label,perks) VALUES (3,'The Thousand Kyoto & Hakone Kowakien Ten-yu','4â€“5 Star Luxury','Private open-air forest hot spring bath, tatami living suites, gourmet seasonal dining.');
INSERT INTO destinations (id,slug,name,region,image_id) VALUES (4,'canada-rockies','Banff & Jasper, Alberta, Canada','North America',9);
INSERT INTO packages (id,slug,booking_key,destination_id,image_id,title,description,category,badge,days,nights,price_per_person,original_price,currency,rating,review_count,is_luxury) VALUES (4,'canada-rockies','canada',4,9,'Canadian Rockies Majestic Wilderness','Turquoise glacial lakes, snowcapped mountain ranges, wildlife safaris, and cozy alpine lodge firesides.','nature','Adventure',7,6,27500000,30500000,'IDR',4.7,96,0);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'feature','Park Passes',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'feature','Lake Louise Canoe',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'feature','Glacier Ice Walk',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'feature','Luxury Lodges',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'included','6 nights in premium mountain lodges & Fairmont resorts',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'included','All National Park entrance fees and permits',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'included','Icefields Parkway Ice Explorer and Skywalk excursion',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'included','Lake Louise canoe rental & Banff Gondola tickets',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (4,'included','Comfortable private AC panoramic tour coach',5);
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,1,'Calgary to Banff National Park','Scenic mountain shuttle from Calgary to Banff township, check into your rustic-chic alpine lodge.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,2,'Lake Louise & Moraine Lake Glacial Wonders','Early morning canoe ride on Lake Louise and breathtaking walk along the Valley of the Ten Peaks.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,3,'Icefields Parkway & Columbia Icefield Glacier','Drive the worldâ€™s most scenic highway; board an all-terrain Ice Explorer onto Athabasca Glacier.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,4,'Jasper National Park & Maligne Canyon','Explore Jasperâ€™s deep limestone canyon waterfalls, wildlife watching (elk, bighorn sheep, and bears).');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,5,'Maligne Lake Boat Cruise to Spirit Island','Iconic boat excursion across crystal waters to the sacred Spirit Island viewpoint.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,6,'Banff Gondola & Upper Hot Springs Relax','Ride the gondola to the summit of Sulphur Mountain, followed by evening thermal mineral soaking.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (4,7,'Bow River Trail & Calgary Departure','Morning nature walk along Bow Falls, souvenir shopping, and airport transfer.');
INSERT INTO package_accommodations (package_id,name,star_label,perks) VALUES (4,'Rimrock Resort Hotel Banff & Fairmont Chateau Lake Louise','4.5-Star Alpine','Panoramic mountain vista balconies, indoor mineral pool, wood-burning fireplaces.');
INSERT INTO destinations (id,slug,name,region,image_id) VALUES (5,'bali-sanctuary','Ubud & Nusa Penida, Indonesia','Asia',2);
INSERT INTO packages (id,slug,booking_key,destination_id,image_id,title,description,category,badge,days,nights,price_per_person,original_price,currency,rating,review_count,is_luxury) VALUES (5,'bali-sanctuary',NULL,5,2,'Bali Tropical Culture & Nusa Penida','Lush emerald rice terraces, sacred water temples, artisan craft villages, and Nusa Penida cliff viewpoints.','culture','Best Value',5,4,14900000,17900000,'IDR',4.8,167,0);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'feature','Private Villa',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'feature','Nusa Penida Boat',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'feature','Floating Breakfast',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'feature','Spa Massage',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'included','4 nights in a 5-Star Private Jungle Pool Villa in Ubud',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'included','Private dedicated car & English-speaking local driver',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'included','Full-day Nusa Penida private boat & tour package',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'included','2-Hour Traditional Balinese spa & flower bath treatment',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (5,'included','All temple entry tickets & sarong rentals',5);
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (5,1,'Denpasar to Ubud Jungle Pool Villa','Airport VIP escort to your secluded pool villa in Ubud, evening Balinese Kecak fire dance performance.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (5,2,'Tegalalang Rice Terraces & Tirta Empul','Sunrise stroll in lush terraces, Bali swing experience, and sacred holy water cleansing ceremony.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (5,3,'Speedboat to Nusa Penida: Kelingking & Broken Beach','Day trip to Nusa Penida, photo stop at T-Rex cliff, Angelâ€™s Billabong, and snorkeling with manta rays.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (5,4,'Ubud Cooking Class & 2-Hour Herbal Spa','Morning organic farm market visit, cooking heritage lunch, followed by flower petal bath spa.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (5,5,'Art Market Shopping & Airport Transfer','Pick up handwoven rattan bags and silver jewelry before transfer to Ngurah Rai Airport.');
INSERT INTO package_accommodations (package_id,name,star_label,perks) VALUES (5,'Komaneka at Bisma & Maya Ubud Resort','5-Star Sanctuary','Private infinity plunge pool overlooking rainforest river valley, daily yoga sessions.');
INSERT INTO destinations (id,slug,name,region,image_id) VALUES (6,'swiss-alps','Zermatt, Interlaken & Lucerne, Switzerland','Europe',3);
INSERT INTO packages (id,slug,booking_key,destination_id,image_id,title,description,category,badge,days,nights,price_per_person,original_price,currency,rating,review_count,is_luxury) VALUES (6,'swiss-alps',NULL,6,3,'Swiss Alps Panoramic Express','Glacier Express first-class panoramic rail, majestic Matterhorn peak views, Lake Lucerne boat cruise, and Swiss fondue.','nature','Luxury Escape',7,6,36500000,41500000,'IDR',4.9,112,1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'feature','Swiss Travel Pass',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'feature','Glacier Express',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'feature','Gornergrat Train',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'feature','Fondue Dining',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'included','6 nights in premium 4â€“5 star Swiss Alpine chalets and hotels',1);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'included','First Class 8-day Swiss Travel Pass for all trains and boats',2);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'included','Seat reservations on the iconic Glacier Express',3);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'included','Jungfraujoch Top of Europe & Gornergrat excursion passes',4);
INSERT INTO package_items (package_id,item_type,description,sort_order) VALUES (6,'included','Authentic Swiss fondue & raclette dining experience',5);
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,1,'Zurich to Lucerne & Lake Steamer Cruise','First class rail to historic Lucerne, wooden Chapel Bridge stroll, and evening sunset paddle steamer.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,2,'Mount Pilatus Golden Round Trip','Worldâ€™s steepest cogwheel railway to Pilatus summit, aerial cableway down, chocolate tasting tour.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,3,'Interlaken & Jungfraujoch Top of Europe','Cogwheel train into the heart of the Eiger to Jungfraujoch ice palace, 3,454m above sea level.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,4,'Glacier Express Ride to Car-Free Zermatt','Famous panoramic carriage with glass roof across alpine viaducts to the foot of the Matterhorn.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,5,'Gornergrat Alpine Cogwheel & Lake Riffelsee','View 29 four-thousand-meter peaks, mirror reflection photos at Riffelsee, traditional fondue dinner.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,6,'Glacier Paradise & Alpine Village Leisure','Highest cable car station in Europe, ice tubing, shopping on Zermattâ€™s charming Bahnhofstrasse.');
INSERT INTO package_itineraries (package_id,day_number,title,description) VALUES (6,7,'Scenic Train to Zurich & Farewell','First-class rail to Zurich Airport, tax-free watch shopping, and flight home.');
INSERT INTO package_accommodations (package_id,name,star_label,perks) VALUES (6,'Omnia Zermatt & Victoria-Jungfrau Grand Hotel','5-Star Swiss Deluxe','Matterhorn view private balconies, alpine spa with outdoor heated saline pool.');
INSERT INTO pricing_plans (name,price_per_person,currency) VALUES ('Economy Explorer',7500000,'IDR');
INSERT INTO pricing_plans (name,price_per_person,currency) VALUES ('Standard Comfort',19500000,'IDR');
INSERT INTO pricing_plans (name,price_per_person,currency) VALUES ('Luxury Platinum',33500000,'IDR');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('brand','Travel','Brand website');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('phone','+62 21 5000 1234','Nomor contoh dari HTML; verifikasi sebelum publikasi');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('whatsapp','+62 812 0000 1234','Nomor contoh dari HTML');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('email','hello@travel.example','Alamat contoh, bukan mailbox aktif');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('office_address','Jl. Jend. Sudirman Kav. 21, Jakarta 12920, Indonesia','Alamat pada Contact');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('office_hours','Mon–Sat, 08:00–20:00 WIB','Jam Contact');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('office_timezone','Asia/Jakarta','Timezone kantor');
INSERT INTO site_settings (setting_key,setting_value,description) VALUES ('currency','IDR','Mata uang harga paket');
COMMIT;

-- Contoh query katalog beserta gambar:
-- SELECT p.title,p.price_per_person,d.name AS destination,m.file_path AS image
-- FROM packages p JOIN destinations d ON d.id=p.destination_id
-- LEFT JOIN media_assets m ON m.id=p.image_id WHERE p.is_active=1;

-- Frame Home: aset 300 JPG ada di assets/journey/ezgif-frame-001.jpg sampai 300.jpg.
-- Urutan frame dikelola home-motion.js, bukan dimuat 300 baris setiap query katalog.
