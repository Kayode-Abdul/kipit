<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = env('PAYSTACK_SECRET_KEY', '');
    }

    /**
     * Initialize a Paystack transaction.
     *
     * @param string $email
     * @param float $amount Amount in the major currency unit (e.g., NGN, USD)
     * @param string $currency (e.g. NGN, USD, GHS)
     * @param array $metadata
     * @return array|null
     */
    public function initializeTransaction(string $email, float $amount, string $currency, array $metadata = []): ?array
    {
        // Paystack expects amount in the lowest subunit (e.g., kobo/cents)
        $amountInSubunit = (int) ($amount * 100);

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email' => $email,
                'amount' => $amountInSubunit,
                'currency' => $currency,
                'metadata' => $metadata,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Paystack Initialization Failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Verify a Paystack transaction by reference.
     *
     * @param string $reference
     * @return array|null
     */
    public function verifyTransaction(string $reference): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
