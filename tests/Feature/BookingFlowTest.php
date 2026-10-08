<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createSeedPackage(string $bookingKey = 'greece', float $price = 19500000): Package
    {
        $image = MediaAsset::create([
            'file_path' => "assets/{$bookingKey}.png",
            'media_type' => 'image',
            'alt_text' => ucfirst($bookingKey).' Tour',
        ]);

        $destination = Destination::create([
            'slug' => "{$bookingKey}-destination",
            'name' => ucfirst($bookingKey).', Europe',
            'region' => 'Europe',
            'image_id' => $image->id,
        ]);

        return Package::create([
            'slug' => "{$bookingKey}-package",
            'booking_key' => $bookingKey,
            'destination_id' => $destination->id,
            'image_id' => $image->id,
            'title' => ucfirst($bookingKey).' Islands Escape',
            'description' => 'Experience an unforgettable island journey.',
            'category' => 'beach',
            'badge' => 'Best Seller',
            'days' => 6,
            'nights' => 5,
            'price_per_person' => $price,
            'original_price' => $price + 3000000,
            'currency' => 'IDR',
            'rating' => 4.8,
            'review_count' => 120,
            'is_luxury' => false,
            'is_active' => true,
        ]);
    }

    public function test_booking_step_1_page_renders_successfully(): void
    {
        Page::create([
            'slug' => 'bookings',
            'html_file' => 'bookings.html',
            'title' => 'Bookings — Travel Around the World',
            'meta_description' => 'Plan your next Travel getaway.',
        ]);

        $this->createSeedPackage('greece');

        $response = $this->get('/bookings');

        $response->assertStatus(200);
        $response->assertSee('Your next chapter', false);
        $response->assertSee('starts here.', false);
        $response->assertSee('Where are we heading?', false);
        $response->assertSee('Plan your trip', false);
        $response->assertSee('id="trip-form"', false);
        $response->assertSee('assets/greece.png', false);
    }

    public function test_booking_step_2_review_page_renders_successfully(): void
    {
        Page::create([
            'slug' => 'booking-review',
            'html_file' => 'booking-review.html',
            'title' => 'Review Your Details — Travel',
            'meta_description' => 'Plan your next Travel getaway.',
        ]);

        $response = $this->get('/booking-review');

        $response->assertStatus(200);
        $response->assertSee('Let’s get the details right.', false);
        $response->assertSee('data-booking-step="2"', false);
        $response->assertSee('Review your details', false);
        $response->assertSee('id="flow-content"', false);
    }

    public function test_booking_step_3_ready_page_renders_successfully(): void
    {
        Page::create([
            'slug' => 'booking-ready',
            'html_file' => 'booking-ready.html',
            'title' => 'Ready to Explore — Travel',
            'meta_description' => 'Plan your next Travel getaway.',
        ]);

        $response = $this->get('/booking-ready');

        $response->assertStatus(200);
        $response->assertSee('Your next adventure awaits.', false);
        $response->assertSee('data-booking-step="3"', false);
        $response->assertSee('Pilih Metode Pembayaran', false);
        $response->assertSee('Cash / Bayar di Kantor', false);
        $response->assertSee('Payment Gateway (Online)', false);
        $response->assertSee('id="payment-modal"', false);
    }

    public function test_store_booking_persists_in_database_and_returns_json(): void
    {
        $package = $this->createSeedPackage('greece', 19500000);
        $departureDate = date('Y-m-d', strtotime('+7 days'));

        $payload = [
            'package' => 'greece',
            'departure' => $departureDate,
            'travelers' => 2,
            'origin' => 'Jakarta Soekarno-Hatta (CGK)',
            'name' => 'Budi Pratama',
            'email' => 'budi.pratama@example.com',
            'notes' => 'Vegetarian meal preference.',
        ];

        $response = $this->postJson('/bookings', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Trip draft created successfully.',
        ]);

        $this->assertDatabaseHas('bookings', [
            'package_id' => $package->id,
            'departure_city' => 'Jakarta Soekarno-Hatta (CGK)',
            'travelers' => 2,
            'lead_name' => 'Budi Pratama',
            'lead_email' => 'budi.pratama@example.com',
            'preview_status' => 'draft',
            'unit_price_snapshot' => 19500000,
        ]);
    }

    public function test_store_booking_standard_form_redirects_to_review(): void
    {
        $package = $this->createSeedPackage('greece');
        $departureDate = date('Y-m-d', strtotime('+10 days'));

        $response = $this->post('/bookings', [
            'package' => 'greece',
            'departure' => $departureDate,
            'travelers' => 3,
            'origin' => 'Surabaya',
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
        ]);

        $response->assertRedirect('/booking-review');
        $this->assertDatabaseHas('bookings', [
            'lead_name' => 'Dewi Lestari',
            'travelers' => 3,
        ]);
    }

    public function test_update_booking_status(): void
    {
        $package = $this->createSeedPackage('greece');

        $booking = Booking::create([
            'reference_code' => 'TRV-2026-9876',
            'package_id' => $package->id,
            'departure_date' => date('Y-m-d', strtotime('+14 days')),
            'departure_city' => 'Bandung',
            'travelers' => 2,
            'lead_name' => 'Andi Wijaya',
            'lead_email' => 'andi@example.com',
            'unit_price_snapshot' => 19500000,
            'currency' => 'IDR',
            'estimated_total' => 39000000,
            'preview_status' => 'draft',
        ]);

        $response = $this->withSession(['booking_reference_codes' => [$booking->reference_code]])->patchJson("/bookings/{$booking->reference_code}/status", [
            'status' => 'reviewed',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('bookings', [
            'reference_code' => 'TRV-2026-9876',
            'preview_status' => 'reviewed',
        ]);
    }

    public function test_validation_fails_for_past_departure_date(): void
    {
        $this->createSeedPackage('greece');
        $pastDate = date('Y-m-d', strtotime('-2 days'));

        $response = $this->postJson('/bookings', [
            'package' => 'greece',
            'departure' => $pastDate,
            'travelers' => 2,
            'origin' => 'Jakarta',
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['departure']);
    }

    public function test_validation_boundary_travelers_count(): void
    {
        $this->createSeedPackage('greece');
        $validDate = date('Y-m-d', strtotime('+5 days'));

        // Below min: 0
        $responseZero = $this->postJson('/bookings', [
            'package' => 'greece',
            'departure' => $validDate,
            'travelers' => 0,
            'origin' => 'Jakarta',
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);
        $responseZero->assertStatus(422);
        $responseZero->assertJsonValidationErrors(['travelers']);

        // Above max: 7
        $responseSeven = $this->postJson('/bookings', [
            'package' => 'greece',
            'departure' => $validDate,
            'travelers' => 7,
            'origin' => 'Jakarta',
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);
        $responseSeven->assertStatus(422);
        $responseSeven->assertJsonValidationErrors(['travelers']);

        // Lower boundary: 1
        $responseOne = $this->postJson('/bookings', [
            'package' => 'greece',
            'departure' => $validDate,
            'travelers' => 1,
            'origin' => 'Jakarta',
            'name' => 'Solo Traveler',
            'email' => 'solo@example.com',
        ]);
        $responseOne->assertStatus(201);

        // Upper boundary: 6
        $responseSix = $this->postJson('/bookings', [
            'package' => 'greece',
            'departure' => $validDate,
            'travelers' => 6,
            'origin' => 'Jakarta',
            'name' => 'Group Leader',
            'email' => 'group@example.com',
        ]);
        $responseSix->assertStatus(201);
    }

    public function test_legacy_html_urls_redirect_permanently(): void
    {
        $this->get('/bookings.html')->assertRedirect('/bookings');
        $this->get('/booking-review.html')->assertRedirect('/booking-review');
        $this->get('/booking-ready.html')->assertRedirect('/booking-ready');
    }

    #[TestWith(['missing-package'])]
    #[TestWith(['inactive-package'])]
    public function test_unavailable_package_returns_422_without_creating_booking(string $selection): void
    {
        $this->freezeTime();
        $package = $this->createSeedPackage('greece');
        if ($selection === 'inactive-package') {
            $package->update(['is_active' => false]);
            $selection = 'greece';
        }

        $this->postJson('/bookings', $this->bookingPayload($selection))
            ->assertUnprocessable()->assertJsonValidationErrors('package');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_status_is_private_to_the_creating_session(): void
    {
        $this->freezeTime();
        $this->createSeedPackage();
        $created = $this->postJson('/bookings', $this->bookingPayload('greece'))->assertCreated();
        $referenceCode = $created->json('reference_code');

        $this->patchJson("/bookings/{$referenceCode}/status", ['status' => 'reviewed'])
            ->assertOk()->assertJsonPath('booking.preview_status', 'reviewed');
        $this->assertDatabaseHas('bookings', ['reference_code' => $referenceCode, 'preview_status' => 'reviewed']);

        $this->flushSession();
        $this->patchJson("/bookings/{$referenceCode}/status", ['status' => 'ready'])
            ->assertNotFound()->assertJsonMissingPath('booking');
        $this->assertDatabaseHas('bookings', ['reference_code' => $referenceCode, 'preview_status' => 'reviewed']);
    }

    /** @return array<string, string|int> */
    private function bookingPayload(string $selection): array
    {
        return [
            'package' => $selection,
            'departure' => now()->addWeek()->toDateString(),
            'travelers' => 2,
            'origin' => 'Jakarta',
            'name' => 'Test Traveler',
            'email' => 'traveler@example.com',
        ];
    }

    #[TestWith(['slug'])]
    #[TestWith(['id'])]
    public function test_active_package_can_be_selected_by_slug_or_id(string $identifier): void
    {
        $this->freezeTime();
        $package = $this->createSeedPackage();
        $this->postJson('/bookings', $this->bookingPayload((string) $package->{$identifier}))
            ->assertCreated()->assertJsonPath('booking.package_id', $package->id);
        $this->assertDatabaseHas('bookings', ['package_id' => $package->id, 'estimated_total' => 39000000]);
    }
}
