<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected string $testServerKey = 'SB-Mid-server-test-secret-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['midtrans.server_key' => $this->testServerKey]);
        config(['midtrans.client_key' => 'SB-Mid-client-test-client-key-67890']);
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

    private function createBooking(Package $package, int $travelers = 1): Booking
    {
        $unitPrice = (float) $package->price_per_person;

        return Booking::create([
            'reference_code' => 'TRV-2026-TESTBOOKING01',
            'package_id' => $package->id,
            'departure_date' => date('Y-m-d', strtotime('+10 days')),
            'departure_city' => 'Jakarta',
            'travelers' => $travelers,
            'lead_name' => 'Budi Santoso',
            'lead_email' => 'budi.santoso@example.com',
            'notes' => 'Vegetarian',
            'unit_price_snapshot' => $unitPrice,
            'currency' => 'IDR',
            'estimated_total' => $unitPrice * $travelers,
            'preview_status' => 'draft',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Test 1: Create payment.
     * Expected: Order status pending, payment record created, Snap Token returned.
     */
    public function test_01_create_payment_initiates_snap_token_and_sets_pending_status(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 2);

        $mockMidtrans = Mockery::mock(MidtransService::class);
        $mockMidtrans->shouldReceive('createSnapToken')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['transaction_details']['gross_amount'] === 200000
                    && $params['customer_details']['email'] === 'budi.santoso@example.com';
            }))
            ->andReturn('dummy-snap-token-abc123xyz');
        $this->app->instance(MidtransService::class, $mockMidtrans);

        $response = $this->withSession(['booking_reference_codes' => [$booking->reference_code]])
            ->postJson("/bookings/{$booking->reference_code}/payment");

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
            'snap_token' => 'dummy-snap-token-abc123xyz',
            'gross_amount' => 200000,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'snap_token' => 'dummy-snap-token-abc123xyz',
            'gross_amount' => 200000,
            'transaction_status' => 'pending',
        ]);
    }

    /**
     * Test 2: Manipulasi nominal frontend.
     * Frontend sends manipulated gross_amount: Rp1 instead of database Rp100.000.
     * Expected: Midtrans strictly recalculates and uses Rp100.000 from server database.
     */
    public function test_02_price_manipulation_from_frontend_is_rejected_and_recalculated_server_side(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $mockMidtrans = Mockery::mock(MidtransService::class);
        $mockMidtrans->shouldReceive('createSnapToken')
            ->once()
            ->with(Mockery::on(function ($params) {
                // Must be 100000, NEVER 1
                return $params['transaction_details']['gross_amount'] === 100000;
            }))
            ->andReturn('dummy-snap-token-price-integrity');
        $this->app->instance(MidtransService::class, $mockMidtrans);

        $response = $this->withSession(['booking_reference_codes' => [$booking->reference_code]])
            ->postJson("/bookings/{$booking->reference_code}/payment", [
                'gross_amount' => 1,
                'total' => 1,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('gross_amount', 100000);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'gross_amount' => 100000,
        ]);
    }

    /**
     * Test 3: Invalid webhook signature.
     * Expected: HTTP 403 Forbidden, database remains unchanged.
     */
    public function test_03_webhook_with_invalid_signature_is_rejected(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-003',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
        ]);

        $payload = [
            'order_id' => 'ORDER-TEST-003',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-fabricated-signature-hash',
        ];

        $response = $this->postJson('/payments/midtrans/webhook', $payload);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Invalid Midtrans signature']);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'transaction_status' => 'pending',
            'paid_at' => null,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Test 4: Valid settlement webhook.
     * Expected: payment = paid, order = paid, paid_at is populated.
     */
    public function test_04_valid_settlement_webhook_marks_payment_and_booking_as_paid(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-004',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
        ]);

        $signatureKey = hash('sha512', 'ORDER-TEST-004'.'200'.'100000.00'.$this->testServerKey);

        $payload = [
            'order_id' => 'ORDER-TEST-004',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_id' => 'midtrans-trx-4444',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'signature_key' => $signatureKey,
        ];

        $response = $this->postJson('/payments/midtrans/webhook', $payload);

        $response->assertOk();
        $response->assertJson(['message' => 'OK']);

        $payment->refresh();
        $this->assertSame('paid', $payment->transaction_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('midtrans-trx-4444', $payment->transaction_id);

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
    }

    /**
     * Test 5: Duplicate settlement webhook.
     * Expected: Idempotent handling, no duplicate changes or journals.
     */
    public function test_05_duplicate_settlement_webhook_is_handled_idempotently(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-005',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
        ]);

        $signatureKey = hash('sha512', 'ORDER-TEST-005'.'200'.'100000.00'.$this->testServerKey);

        $payload = [
            'order_id' => 'ORDER-TEST-005',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_id' => 'midtrans-trx-5555',
            'transaction_status' => 'settlement',
            'signature_key' => $signatureKey,
        ];

        // First webhook
        $this->postJson('/payments/midtrans/webhook', $payload)->assertOk();
        $payment->refresh();
        $firstPaidAt = $payment->paid_at;
        $this->assertNotNull($firstPaidAt);

        // Second webhook (duplicate)
        $secondResponse = $this->postJson('/payments/midtrans/webhook', $payload);
        $secondResponse->assertOk();
        $secondResponse->assertJson(['message' => 'Already settled']);

        $payment->refresh();
        $this->assertSame($firstPaidAt->toDateTimeString(), $payment->paid_at->toDateTimeString());
        $this->assertSame(1, Payment::where('provider_order_id', 'ORDER-TEST-005')->count());
    }

    /**
     * Test 6: Expired transaction.
     * Expected: payment = expired, booking = expired, not considered paid.
     */
    public function test_06_expired_transaction_updates_status_without_marking_paid(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-006',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
        ]);

        $signatureKey = hash('sha512', 'ORDER-TEST-006'.'200'.'100000.00'.$this->testServerKey);

        $payload = [
            'order_id' => 'ORDER-TEST-006',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_id' => 'midtrans-trx-6666',
            'transaction_status' => 'expire',
            'signature_key' => $signatureKey,
        ];

        $response = $this->postJson('/payments/midtrans/webhook', $payload);

        $response->assertOk();

        $payment->refresh();
        $this->assertSame('expired', $payment->transaction_status);
        $this->assertNull($payment->paid_at);

        $booking->refresh();
        $this->assertSame('expired', $booking->payment_status);
    }

    /**
     * Test 7: Denied transaction.
     * Expected: payment = failed, booking = failed, no income recorded.
     */
    public function test_07_denied_transaction_marks_payment_as_failed(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-007',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
        ]);

        $signatureKey = hash('sha512', 'ORDER-TEST-007'.'200'.'100000.00'.$this->testServerKey);

        $payload = [
            'order_id' => 'ORDER-TEST-007',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'transaction_id' => 'midtrans-trx-7777',
            'transaction_status' => 'deny',
            'signature_key' => $signatureKey,
        ];

        $response = $this->postJson('/payments/midtrans/webhook', $payload);

        $response->assertOk();

        $payment->refresh();
        $this->assertSame('failed', $payment->transaction_status);
        $this->assertNull($payment->paid_at);

        $booking->refresh();
        $this->assertSame('failed', $booking->payment_status);
    }

    /**
     * Test 8: Amount mismatch.
     * Expected: payment is NOT marked paid, security log written, HTTP 400.
     */
    public function test_08_amount_mismatch_prevents_payment_from_being_marked_paid(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-008',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
        ]);

        // Mismatched amount: 50000 instead of 100000
        $signatureKey = hash('sha512', 'ORDER-TEST-008'.'200'.'50000.00'.$this->testServerKey);

        $payload = [
            'order_id' => 'ORDER-TEST-008',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'transaction_status' => 'settlement',
            'signature_key' => $signatureKey,
        ];

        Log::spy();

        $response = $this->postJson('/payments/midtrans/webhook', $payload);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Gross amount mismatch']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Midtrans webhook gross amount mismatch', Mockery::subset([
                'order_id' => 'ORDER-TEST-008',
                'received_amount' => 50000.0,
                'expected_amount' => 100000.0,
            ]));

        $payment->refresh();
        $this->assertSame('pending', $payment->transaction_status);
        $this->assertNull($payment->paid_at);

        $booking->refresh();
        $this->assertSame('unpaid', $booking->payment_status);
    }

    /**
     * Test 9: Order belonging to another user / session.
     * Expected: 403 Forbidden.
     */
    public function test_09_order_access_belonging_to_another_session_returns_403_forbidden(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        // Session does NOT contain booking reference code
        $response = $this->withSession(['booking_reference_codes' => ['TRV-SOME-OTHER-CODE']])
            ->postJson("/bookings/{$booking->reference_code}/payment");

        $response->assertStatus(403);
    }

    /**
     * Test 10: Server Key exposure check.
     * Expected: Server Key is never rendered in HTML, JS scripts, or API responses.
     */
    public function test_10_server_key_is_never_exposed_in_html_or_responses(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $responseStep3 = $this->withSession(['booking_reference_codes' => [$booking->reference_code]])
            ->get('/booking-ready');

        $responseStep3->assertOk();
        $responseStep3->assertDontSee($this->testServerKey);
        $responseStep3->assertSee('SB-Mid-client-test-client-key-67890'); // Client key is allowed

        $statusResponse = $this->withSession(['booking_reference_codes' => [$booking->reference_code]])
            ->getJson("/bookings/{$booking->reference_code}/payment-status");

        $statusResponse->assertOk();
        $statusResponse->assertJsonMissing(['server_key' => $this->testServerKey]);
        $this->assertStringNotContainsString($this->testServerKey, $statusResponse->getContent());
    }

    /**
     * Test 11: Payment confirmation settles booking and payment status.
     * Expected: payment_status = paid, transaction_status = paid.
     */
    public function test_11_payment_confirmation_endpoint_settles_booking_and_payment_status(): void
    {
        $package = $this->createPackage(100000.00);
        $booking = $this->createBooking($package, 1);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'provider_order_id' => 'ORDER-TEST-011',
            'gross_amount' => 100000,
            'currency' => 'IDR',
            'transaction_status' => 'pending',
            'snap_token' => 'SB-SNAP-SANDBOX-TEST11',
        ]);

        $response = $this->withSession(['booking_reference_codes' => [$booking->reference_code]])
            ->postJson("/bookings/{$booking->reference_code}/confirm", [
                'payment_type' => 'QRIS (GoPay)',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'payment_status' => 'paid',
        ]);

        $booking->refresh();
        $payment->refresh();

        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('paid', $payment->transaction_status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('QRIS (GoPay)', $payment->payment_type);
    }
}
