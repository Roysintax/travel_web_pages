<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationPageTest extends TestCase
{
    use RefreshDatabase;

    private function createDestinationWithPackage(string $slug, string $name, string $region, int $price = 19500000): Destination
    {
        $media = MediaAsset::create([
            'file_path' => "assets/{$slug}.png",
            'media_type' => 'image',
            'alt_text' => "{$name} landscape",
        ]);

        $destination = Destination::create([
            'slug' => $slug,
            'name' => $name,
            'region' => $region,
            'image_id' => $media->id,
        ]);

        Package::create([
            'slug' => "{$slug}-pkg",
            'booking_key' => $slug,
            'destination_id' => $destination->id,
            'image_id' => $media->id,
            'title' => "{$name} Adventure Tour",
            'description' => 'Unforgettable scenery and culture.',
            'category' => 'nature',
            'badge' => 'Best Seller',
            'days' => 6,
            'nights' => 5,
            'price_per_person' => $price,
            'currency' => 'IDR',
            'rating' => 4.8,
            'review_count' => 120,
            'is_luxury' => false,
            'is_active' => true,
        ]);

        return $destination;
    }

    public function test_destinations_page_renders_destinations_and_top_deals_from_database(): void
    {
        $this->createDestinationWithPackage('greece', 'Santorini & Mykonos, Greece', 'Europe', 19500000);
        $this->createDestinationWithPackage('maldives', 'North Malé Atoll, Maldives', 'Asia', 24500000);

        Page::create([
            'slug' => 'destinations',
            'html_file' => 'destinations.html',
            'title' => 'Destinations — Travel Around the World',
            'meta_description' => 'Explore destinations around the world.',
        ]);

        $response = $this->get(route('destinations.index'));

        $response->assertOk()
            ->assertViewIs('destinations')
            ->assertViewHas('destinations')
            ->assertViewHas('topDeals')
            ->assertViewHas('regions')
            ->assertSee('Santorini &amp; Mykonos, Greece', false)
            ->assertSee('North Malé Atoll, Maldives')
            ->assertSee('Europe')
            ->assertSee('Asia')
            ->assertSee('Rp 19.500.000')
            ->assertSee('Rp 24.500.000')
            ->assertSee('gemini_generated_video_c9ca0f42.mp4')
            ->assertSee('Top Trips')
            ->assertSee('Travel with confidence');
    }

    public function test_destinations_page_handles_empty_database_gracefully(): void
    {
        $response = $this->get(route('destinations.index'));

        $response->assertOk()
            ->assertSee('0 destinations to explore')
            ->assertSee('No destinations found in catalog.');
    }

    public function test_destinations_escapes_malicious_xss_inputs(): void
    {
        $this->createDestinationWithPackage(
            'xss-dest',
            '<script>alert("hacked")</script>',
            'Cyber<script>'
        );

        $response = $this->get(route('destinations.index'));

        $response->assertOk()
            ->assertDontSee('<script>alert("hacked")</script>', false)
            ->assertDontSee('Cyber<script>', false);
    }
}
