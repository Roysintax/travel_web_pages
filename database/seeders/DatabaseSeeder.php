<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::where('email', 'test@example.com')->doesntExist()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $pages = [
            [
                'slug' => 'about',
                'html_file' => 'about.html',
                'title' => 'About Us — Travel Around the World',
                'meta_description' => 'Meet Travel: a brighter way to discover destinations and bring your next holiday together.',
            ],
            [
                'slug' => 'contact',
                'html_file' => 'contact.html',
                'title' => 'Contact Us — Travel Around the World',
                'meta_description' => 'Contact Travel: call, email, visit our office, or chat live with a travel expert to plan your next holiday.',
            ],
            [
                'slug' => 'bookings',
                'html_file' => 'bookings.html',
                'title' => 'Bookings — Travel Around the World',
                'meta_description' => 'Plan your next Travel getaway, choose a destination, and review your trip estimate.',
            ],
            [
                'slug' => 'booking-review',
                'html_file' => 'booking-review.html',
                'title' => 'Review Your Details — Travel',
                'meta_description' => 'Plan your next Travel getaway, choose a destination, and review your trip estimate.',
            ],
            [
                'slug' => 'booking-ready',
                'html_file' => 'booking-ready.html',
                'title' => 'Ready to Explore — Travel',
                'meta_description' => 'Plan your next Travel getaway, choose a destination, and review your trip estimate.',
            ],
            [
                'slug' => 'destinations',
                'html_file' => 'destinations.html',
                'title' => 'Destinations — Travel Around the World',
                'meta_description' => 'Explore world destinations with Travel.',
            ],
            [
                'slug' => 'packages',
                'html_file' => 'packages.html',
                'title' => 'Travel Packages — Travel Around the World',
                'meta_description' => 'Explore all-inclusive holiday packages by Travel: island escapes, cultural journeys, and mountain adventures.',
            ],
        ];

        foreach ($pages as $p) {
            Page::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $settings = [
            ['setting_key' => 'brand', 'setting_value' => 'Travel', 'description' => 'Brand website'],
            ['setting_key' => 'phone', 'setting_value' => '+62 21 5000 1234', 'description' => 'Nomor contoh dari HTML'],
            ['setting_key' => 'whatsapp', 'setting_value' => '+62 812 0000 1234', 'description' => 'Nomor WhatsApp contoh'],
            ['setting_key' => 'email', 'setting_value' => 'hello@travel.example', 'description' => 'Email support contoh'],
            ['setting_key' => 'office_address', 'setting_value' => 'Jl. Jend. Sudirman Kav. 21, Jakarta 12920, Indonesia', 'description' => 'Alamat kantor'],
            ['setting_key' => 'office_hours', 'setting_value' => 'Mon–Sat, 08:00–20:00 WIB', 'description' => 'Jam operasional'],
            ['setting_key' => 'office_timezone', 'setting_value' => 'Asia/Jakarta', 'description' => 'Timezone kantor'],
            ['setting_key' => 'currency', 'setting_value' => 'IDR', 'description' => 'Mata uang'],
        ];

        foreach ($settings as $item) {
            SiteSetting::firstOrCreate(['setting_key' => $item['setting_key']], $item);
        }
    }
}
