<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\PackageAccommodation;
use App\Models\PackageItem;
use App\Models\PackageItinerary;
use App\Models\Page;
use App\Models\PricingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagePageTest extends TestCase
{
    use RefreshDatabase;

    private function createFullPackage(string $slug, array $overrides = []): Package
    {
        $image = MediaAsset::create([
            'file_path' => "assets/{$slug}.png",
            'media_type' => 'image',
            'alt_text' => ucfirst($slug).' View',
        ]);

        $destination = Destination::create([
            'slug' => $slug,
            'name' => 'Island, '.ucfirst($slug),
            'region' => 'Europe',
            'image_id' => $image->id,
        ]);

        $package = Package::create(array_merge([
            'slug' => $slug,
            'booking_key' => $slug,
            'destination_id' => $destination->id,
            'image_id' => $image->id,
            'title' => ucfirst($slug).' Luxury Tour',
            'description' => 'A wonderful getaway experience.',
            'category' => 'beach',
            'badge' => 'Best Seller',
            'days' => 6,
            'nights' => 5,
            'price_per_person' => 19500000,
            'original_price' => 22500000,
            'currency' => 'IDR',
            'rating' => 4.8,
            'review_count' => 150,
            'is_luxury' => false,
            'is_active' => true,
        ], $overrides));

        PackageItem::create([
            'package_id' => $package->id,
            'item_type' => 'feature',
            'description' => 'Flights Included',
            'sort_order' => 1,
        ]);

        PackageItem::create([
            'package_id' => $package->id,
            'item_type' => 'included',
            'description' => 'Daily gourmet breakfast',
            'sort_order' => 1,
        ]);

        PackageItinerary::create([
            'package_id' => $package->id,
            'day_number' => 1,
            'title' => 'Arrival & Welcome',
            'description' => 'Transfer to hotel and relax.',
        ]);

        PackageAccommodation::create([
            'package_id' => $package->id,
            'name' => 'Grand Resort Suites',
            'star_label' => '5-Star Luxury',
            'perks' => 'Ocean view balcony and spa access.',
        ]);

        return $package;
    }

    public function test_packages_page_renders_successfully_with_database_records(): void
    {
        $pkg = $this->createFullPackage('greece-islands');

        PricingPlan::create([
            'name' => 'Economy Explorer',
            'price_per_person' => 7500000,
            'currency' => 'IDR',
        ]);

        $page = Page::create([
            'slug' => 'packages',
            'html_file' => 'packages.html',
            'title' => 'Travel Packages — Travel Around the World',
            'meta_description' => 'Meta description for packages page.',
        ]);

        Faq::create([
            'page_id' => $page->id,
            'question' => 'What is included in the package prices?',
            'answer' => 'Flights, transfers, and 5-star resort stays.',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('packages.index'));

        $response->assertOk()
            ->assertViewIs('packages')
            ->assertViewHas('packages')
            ->assertViewHas('packagesJson')
            ->assertViewHas('pricingPlans')
            ->assertViewHas('faqs')
            ->assertSee('Greece-islands Luxury Tour')
            ->assertSee('Economy Explorer')
            ->assertSee('Rp 7.500.000')
            ->assertSee('What is included in the package prices?')
            ->assertSee('Flights, transfers, and 5-star resort stays.');
    }

    public function test_inactive_packages_are_not_shown_in_catalog(): void
    {
        $this->createFullPackage('active-tour', ['title' => 'Active Tour Visible']);
        $this->createFullPackage('hidden-tour', ['title' => 'Hidden Tour Inactive', 'is_active' => false]);

        $response = $this->get(route('packages.index'));

        $response->assertOk()
            ->assertSee('Active Tour Visible')
            ->assertDontSee('Hidden Tour Inactive');
    }

    public function test_packages_page_handles_empty_database_gracefully(): void
    {
        $response = $this->get(route('packages.index'));

        $response->assertOk()
            ->assertSee('Showing <strong>0</strong> packages', false)
            ->assertSee('Choose Your Travel Tier')
            ->assertSee('Build &amp; Estimate Your Package', false);
    }

    public function test_package_and_faq_data_are_safely_escaped_against_xss(): void
    {
        $this->createFullPackage('xss-pkg', [
            'title' => '<script>alert("xss-pkg")</script>',
        ]);

        $page = Page::create([
            'slug' => 'packages',
            'html_file' => 'packages.html',
            'title' => 'Test Page',
        ]);

        Faq::create([
            'page_id' => $page->id,
            'question' => '<img src=x onerror=alert(1)>',
            'answer' => '<script>alert(2)</script>',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('packages.index'));

        $response->assertOk()
            ->assertDontSee('<script>alert("xss-pkg")</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<script>alert(2)</script>', false);
    }
}
