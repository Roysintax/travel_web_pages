<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\MediaAsset;
use App\Models\Package;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createPackage(float $price = 100000.00): Package
    {
        $image = MediaAsset::create([
            'file_path' => 'assets/test-package.png',
            'media_type' => 'image',
            'alt_text' => 'Test Package',
        ]);

        $destination = Destination::create([
            'slug' => 'test-destination',
            'name' => 'Bali, Indonesia',
            'region' => 'Asia',
            'image_id' => $image->id,
        ]);

        return Package::create([
            'slug' => 'test-package',
            'booking_key' => 'bali',
            'destination_id' => $destination->id,
            'image_id' => $image->id,
            'title' => 'Bali Tropical Getaway',
            'description' => 'A wonderful getaway in Bali.',
            'category' => 'beach',
            'badge' => 'Popular',
            'days' => 4,
            'nights' => 3,
            'price_per_person' => $price,
            'original_price' => $price + 50000,
            'currency' => 'IDR',
            'rating' => 4.9,
            'review_count' => 50,
            'is_luxury' => false,
            'is_active' => true,
        ]);
    }

    public function test_local_simulation_records_test_result_without_marking_booking_paid(): void
    {
        $this->app['env'] = 'local';
        config(['midtrans.simulation_enabled' => true, 'midtrans.is_production' => false]);
        $package = $this->createPackage(900000);
        $booking = Booking::create(['reference_code' => 'TEST-SIM', 'package_id' => $package->id, 'departure_date' => '2027-01-01', 'departure_city' => 'Jakarta', 'travelers' => 2, 'lead_name' => 'Tester', 'lead_email' => 'test@example.com', 'unit_price_snapshot' => 100000, 'estimated_total' => 200000, 'currency' => 'IDR']);
        $this->withSession(['booking_reference_codes' => ['TEST-SIM']])->postJson('/bookings/TEST-SIM/payment')->assertOk()->assertJsonPath('simulation', true)->assertJsonPath('gross_amount', 200000);
        $this->postJson('/bookings/TEST-SIM/payment-simulation', ['gross_amount' => 1])->assertOk()->assertJsonPath('transaction_status', 'simulated');
        $this->postJson('/bookings/TEST-SIM/payment-simulation')->assertOk();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['provider' => 'local_simulator', 'gross_amount' => 200000, 'transaction_status' => 'simulated', 'paid_at' => null]);
        $this->assertSame('unpaid', $booking->refresh()->payment_status);
        config(['midtrans.server_key' => 'test-key']);
        $this->postJson('/payments/midtrans/webhook', [
            'order_id' => 'SIM-TEST-SIM', 'status_code' => '200', 'gross_amount' => '200000.00',
            'transaction_status' => 'settlement',
            'signature_key' => hash('sha512', 'SIM-TEST-SIM'.'200'.'200000.00'.'test-key'),
        ])->assertNotFound();
        $this->assertSame('unpaid', $booking->refresh()->payment_status);
    }

    public function test_simulation_is_unavailable_in_production_or_remote_requests(): void
    {
        config(['midtrans.simulation_enabled' => true]);
        $this->app['env'] = 'production';
        $this->postJson('/bookings/ANY/payment-simulation')->assertNotFound();
        $this->app['env'] = 'local';
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.4'])->postJson('/bookings/ANY/payment-simulation')->assertNotFound();
    }

    public function test_simulation_requires_booking_ownership_and_sandbox_mode(): void
    {
        $this->app['env'] = 'local';
        config(['midtrans.simulation_enabled' => true, 'midtrans.is_production' => true]);
        $this->postJson('/bookings/ANY/payment-simulation')->assertNotFound();
        config(['midtrans.is_production' => false]);
        $this->postJson('/bookings/OTHER-SESSION/payment-simulation')->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }
}
