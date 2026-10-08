<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public static function allowsSimulation(Request $request): bool
    {
        return config('midtrans.simulation_enabled', false)
            && ! config('midtrans.is_production', false)
            && app()->environment('local')
            && in_array($request->ip(), ['127.0.0.1', '::1'], true)
            && in_array($request->getHost(), ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }

    public function __construct()
    {
        Config::$serverKey = (string) config('midtrans.server_key');
        Config::$clientKey = (string) config('midtrans.client_key');
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Generate Snap Token for a transaction.
     *
     * @param  array<string, mixed>  $params
     */
    public function createSnapToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }

    /**
     * Verify Midtrans notification signature key.
     * Formula: SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '' || $signatureKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Query Midtrans Status API directly for server-to-server challenge/verification.
     *
     * @return array<string, mixed>|null
     */
    public function getTransactionStatus(string $orderId): ?array
    {
        try {
            $status = Transaction::status($orderId);

            return is_object($status) ? (array) $status : $status;
        } catch (Exception $e) {
            Log::warning('Midtrans transaction status query failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Map Midtrans transaction status and fraud status to local payment status.
     */
    public function mapStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        return match ($transactionStatus) {
            'settlement' => 'paid',
            'capture' => ($fraudStatus === 'accept' || $fraudStatus === null) ? 'paid' : 'challenge',
            'pending' => 'pending',
            'deny' => 'failed',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            'refund', 'partial_refund' => 'refunded',
            default => 'unknown',
        };
    }
}
