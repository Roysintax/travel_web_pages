<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\MidtransService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Generate Midtrans Snap token for a booking.
     * Calculates the amount from the booking's server-side price snapshot.
     */
    public function create(Request $request, string $referenceCode): JsonResponse
    {
        $booking = Booking::where('reference_code', $referenceCode)
            ->with('package')
            ->firstOrFail();

        // Authorization check: booking reference must belong to the user session
        $sessionCodes = (array) $request->session()->get('booking_reference_codes', []);
        abort_unless(
            in_array($booking->reference_code, $sessionCodes, true),
            403,
            'Unauthorized access to this booking.'
        );

        // Security rule: NEVER TRUST FRONTEND AMOUNT. Recalculate strictly server-side.
        $unitPrice = (float) $booking->unit_price_snapshot;
        $travelers = max(1, (int) $booking->travelers);
        $serverCalculatedTotal = (int) round($unitPrice * $travelers);

        abort_if($booking->payment_status === 'paid', 409, 'Booking sudah dibayar.');
        abort_if($serverCalculatedTotal <= 0 || $booking->currency !== 'IDR', 422, 'Pembayaran memerlukan nominal positif dalam IDR.');

        if (MidtransService::allowsSimulation($request)) {
            return response()->json(['success' => true, 'simulation' => true, 'gross_amount' => $serverCalculatedTotal]);
        }

        // If an existing pending payment has a valid snap token, reuse it
        $existingPayment = $booking->payments()
            ->where('transaction_status', 'pending')
            ->whereNotNull('snap_token')
            ->latest()
            ->first();

        if ($existingPayment && ! str_starts_with($existingPayment->snap_token, 'SB-SNAP-SANDBOX-')) {
            return response()->json([
                'success' => true,
                'snap_token' => $existingPayment->snap_token,
                'provider_order_id' => $existingPayment->provider_order_id,
                'gross_amount' => (int) $existingPayment->gross_amount,
                'is_fallback' => false,
            ]);
        }

        $providerOrderId = $booking->reference_code.'-'.Str::upper(Str::random(4));

        $params = [
            'transaction_details' => [
                'order_id' => $providerOrderId,
                'gross_amount' => $serverCalculatedTotal,
            ],
            'customer_details' => [
                'first_name' => $booking->lead_name,
                'email' => $booking->lead_email,
            ],
            'item_details' => [
                [
                    'id' => (string) $booking->package_id,
                    'price' => (int) round($unitPrice),
                    'quantity' => $travelers,
                    'name' => mb_substr($booking->package->title, 0, 50),
                ],
            ],
        ];

        $isFallback = false;
        try {
            $snapToken = $this->midtransService->createSnapToken($params);
        } catch (Exception $e) {
            Log::warning('Midtrans Snap token generation via live API skipped or failed', [
                'booking_reference' => $booking->reference_code,
                'order_id' => $providerOrderId,
                'error' => $e->getMessage(),
            ]);

            // In sandbox mode if keys are not yet configured or live API rejected credentials,
            // generate a sandbox token so checkout flow functions seamlessly
            if (! config('midtrans.is_production')) {
                $snapToken = 'SB-SNAP-SANDBOX-'.Str::upper(Str::random(24));
                $isFallback = true;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to initiate payment gateway. Please try again later.',
                ], 500);
            }
        }

        $payment = $existingPayment ?: new Payment([
            'booking_id' => $booking->id,
            'provider' => 'midtrans',
            'currency' => $booking->currency ?: 'IDR',
        ]);

        $payment->fill([
            'provider_order_id' => $providerOrderId,
            'snap_token' => $snapToken,
            'gross_amount' => $serverCalculatedTotal,
            'transaction_status' => 'pending',
        ])->save();

        $booking->update(['payment_status' => 'pending']);

        return response()->json([
            'success' => true,
            'snap_token' => $payment->snap_token,
            'provider_order_id' => $payment->provider_order_id,
            'gross_amount' => (int) $payment->gross_amount,
            'is_fallback' => $isFallback,
        ], 201);
    }

    /**
     * Confirm / settle payment for sandbox testing.
     */
    public function confirm(Request $request, string $referenceCode): JsonResponse
    {
        $booking = Booking::where('reference_code', $referenceCode)
            ->with('latestPayment')
            ->firstOrFail();

        $sessionCodes = (array) $request->session()->get('booking_reference_codes', []);
        abort_unless(
            in_array($booking->reference_code, $sessionCodes, true),
            403,
            'Unauthorized access to this booking.'
        );

        $payment = $booking->latestPayment;
        if ($payment) {
            $payment->update([
                'transaction_status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => 'MIDTRANS-'.time().'-'.Str::upper(Str::random(6)),
                'payment_type' => $request->input('payment_type', 'qris'),
            ]);
        }

        $booking->update([
            'payment_status' => 'paid',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dikonfirmasi.',
            'booking_reference' => $booking->reference_code,
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Check payment status for a booking.
     */
    public function status(Request $request, string $referenceCode): JsonResponse
    {
        $booking = Booking::where('reference_code', $referenceCode)
            ->with('latestPayment')
            ->firstOrFail();

        $sessionCodes = (array) $request->session()->get('booking_reference_codes', []);
        abort_unless(
            in_array($booking->reference_code, $sessionCodes, true),
            403,
            'Unauthorized access to this booking.'
        );

        $payment = $booking->latestPayment;

        return response()->json([
            'booking_reference' => $booking->reference_code,
            'payment_status' => $booking->payment_status ?: 'unpaid',
            'transaction_status' => $payment?->transaction_status ?: 'unpaid',
            'gross_amount' => (float) ($payment?->gross_amount ?: $booking->estimated_total),
            'paid_at' => $payment?->paid_at?->toIso8601String(),
        ]);
    }

    public function simulate(Request $request, string $referenceCode): JsonResponse
    {
        abort_unless(MidtransService::allowsSimulation($request), 404);
        abort_unless(in_array($referenceCode, (array) $request->session()->get('booking_reference_codes', []), true), 403);

        return DB::transaction(function () use ($referenceCode): JsonResponse {
            $booking = Booking::where('reference_code', $referenceCode)->lockForUpdate()->firstOrFail();
            abort_if($booking->payment_status === 'paid', 409, 'Booking sudah dibayar.');
            $amount = (int) round((float) $booking->unit_price_snapshot * $booking->travelers);
            abort_if($amount <= 0 || $booking->currency !== 'IDR', 422);
            $payment = $booking->payments()->firstOrCreate([
                'provider' => 'local_simulator',
                'provider_order_id' => 'SIM-'.$booking->reference_code,
            ], [
                'gross_amount' => $amount,
                'currency' => 'IDR',
                'transaction_status' => 'simulated',
                'metadata' => ['simulation' => true],
            ]);

            return response()->json(['success' => true, 'simulation' => true, 'transaction_status' => $payment->transaction_status, 'gross_amount' => (int) $payment->gross_amount, 'message' => 'Simulasi selesai. Tidak ada dana dipindahkan; booking tetap belum dibayar.']);
        });
    }
}
