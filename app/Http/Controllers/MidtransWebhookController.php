<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Handle incoming Midtrans HTTP Webhook notification.
     * Idempotent, transaction-safe, amount-verified, signature-verified.
     */
    public function handle(Request $request): JsonResponse
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');

        // 1. Verify Webhook Signature (Section 23)
        $isSignatureValid = $this->midtransService->verifySignature(
            $orderId,
            $statusCode,
            $grossAmount,
            $signatureKey
        );

        if (! $isSignatureValid) {
            Log::warning('Midtrans webhook invalid signature rejected', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'message' => 'Invalid Midtrans signature',
            ], 403);
        }

        // 2. Database Transaction with Row Locking (Sections 28, 29, 30)
        return DB::transaction(function () use ($request, $orderId, $grossAmount) {
            // Find payment by provider_order_id first, fallback to booking reference code
            $payment = Payment::where('provider_order_id', $orderId)
                ->where('provider', 'midtrans')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $booking = Booking::where('reference_code', $orderId)
                    ->lockForUpdate()
                    ->first();

                if ($booking) {
                    $payment = $booking->payments()
                        ->where('provider', 'midtrans')
                        ->lockForUpdate()
                        ->latest()
                        ->first();
                }
            }

            if (! $payment) {
                Log::warning('Midtrans webhook payment record not found', [
                    'order_id' => $orderId,
                ]);

                return response()->json([
                    'message' => 'Order not found',
                ], 404);
            }

            $booking = Booking::where('id', $payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Amount Verification (Section 25)
            $receivedAmount = (float) $grossAmount;
            $expectedAmount = (float) $payment->gross_amount;

            if (abs($receivedAmount - $expectedAmount) > 0.01) {
                Log::warning('Midtrans webhook gross amount mismatch', [
                    'order_id' => $orderId,
                    'received_amount' => $receivedAmount,
                    'expected_amount' => $expectedAmount,
                ]);

                return response()->json([
                    'message' => 'Gross amount mismatch',
                ], 400);
            }

            // 4. Map Transaction Status (Section 26, 27)
            $transactionStatus = (string) $request->input('transaction_status');
            $fraudStatus = $request->input('fraud_status') ? (string) $request->input('fraud_status') : null;
            $localStatus = $this->midtransService->mapStatus($transactionStatus, $fraudStatus);

            // 5. Idempotency Check (Section 30)
            if ($payment->transaction_status === 'paid' && $localStatus === 'paid') {
                Log::info('Midtrans webhook duplicate notification safely acknowledged (idempotent)', [
                    'order_id' => $orderId,
                    'transaction_id' => $request->input('transaction_id'),
                ]);

                return response()->json([
                    'message' => 'Already settled',
                ], 200);
            }

            // 6. Update Payment Record
            $payment->update([
                'transaction_id' => (string) $request->input('transaction_id'),
                'payment_type' => $request->input('payment_type') ? (string) $request->input('payment_type') : $payment->payment_type,
                'transaction_status' => $localStatus,
                'fraud_status' => $fraudStatus,
                'paid_at' => $localStatus === 'paid' ? ($payment->paid_at ?? now()) : null,
                'metadata' => array_filter([
                    'payment_type' => $request->input('payment_type'),
                    'status_code' => $request->input('status_code'),
                    'status_message' => $request->input('status_message'),
                    'transaction_time' => $request->input('transaction_time'),
                    'settlement_time' => $request->input('settlement_time'),
                    'bank' => $request->input('bank'),
                    'va_numbers' => $request->input('va_numbers'),
                ]),
            ]);

            // 7. Update Booking Status & Accounting Hook (Sections 31, 32)
            if ($localStatus === 'paid') {
                $booking->update([
                    'payment_status' => 'paid',
                ]);

                // Accounting hook: record verified income once
                Log::info('Midtrans payment confirmed and verified', [
                    'order_id' => $orderId,
                    'booking_reference' => $booking->reference_code,
                    'gross_amount' => $expectedAmount,
                ]);
            } elseif (in_array($localStatus, ['failed', 'expired', 'cancelled'], true)) {
                $booking->update([
                    'payment_status' => $localStatus,
                ]);
            }

            return response()->json([
                'message' => 'OK',
            ], 200);
        });
    }
}
