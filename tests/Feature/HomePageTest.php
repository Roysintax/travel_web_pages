<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\MediaAsset;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    private function makePackage(string $key, array $overrides = []): Package
    {
        $image = MediaAsset::create(['file_path' => "assets/{$key}.png", 'media_type' => 'image', 'alt_text' => '']);
        $destination = Destination::create(['slug' => $key, 'name' => 'City, '.ucfirst($key), 'region' => 'Asia', 'image_id' => $image->id]);

        return Package::create(array_merge([
            'slug' => "{$key}-pkg", 'booking_key' => $key, 'destination_id' => $destination->id, 'image_id' => $image->id,
            'title' => ucfirst($key).' Trip', 'description' => 'Desc', 'category' => 'beach',
            'days' => 6, 'nights' => 5, 'price_per_person' => 19500000, 'rating' => 4.8,
        ], $overrides));
    }

    public function test_home_renders_featured_packages_from_database(): void
    {
        $this->makePackage('greece');
        $this->makePackage('japan', ['price_per_person' => 33500000]);

        $this->get('/')
            ->assertOk()
            ->assertViewIs('home')
            ->assertSeeInOrder(['Greece Trip', 'Japan Trip'])
            ->assertSee('Rp 19.500.000')
            ->assertSee('Rp 33.500.000')
            ->assertSee('6 Days / 5 Nights')
            ->assertSee('♦ Greece', false);
    }

    public function test_inactive_and_non_featured_packages_are_hidden(): void
    {
        $this->makePackage('maldives', ['is_active' => false]);
        $this->makePackage('bali', ['booking_key' => null]);

        $this->get('/')->assertOk()->assertDontSee('Maldives Trip')->assertDontSee('Bali Trip');
    }

    public function test_home_renders_with_empty_catalog(): void
    {
        $this->get('/')->assertOk()->assertSee('No packages are available right now.');
    }

    public function test_package_title_is_escaped(): void
    {
        $this->makePackage('canada', ['title' => '<script>alert(1)</script>']);

        $this->get('/')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
