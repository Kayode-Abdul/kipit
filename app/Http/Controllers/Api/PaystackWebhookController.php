<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivationKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Paystack signature verification
        $signature = $request->header('x-paystack-signature');
        $secret = env('PAYSTACK_SECRET_KEY');

        if (!$signature || $signature !== hash_hmac('sha512', $request->getContent(), $secret)) {
            Log::warning('Invalid Paystack Webhook Signature', ['ip' => $request->ip()]);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null;

        if ($event === 'charge.success') {
            $data = $payload['data'];
            $metadata = $data['metadata'] ?? [];

            if (($metadata['type'] ?? '') === 'subscription_purchase') {
                $tenantId = $metadata['tenant_id'] ?? null;
                $durationInMonths = (int) ($metadata['duration_in_months'] ?? 1);

                if ($tenantId) {
                    $this->generateActivationKey($tenantId, $durationInMonths);
                } else {
                    Log::error('Paystack webhook received subscription purchase but no tenant_id found.', ['data' => $data]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    protected function generateActivationKey(string $tenantId, int $durationInMonths)
    {
        // 1. Find if the tenant already has an unused or active key to determine expiration logic
        $latestKey = ActivationKey::where('tenant_id', $tenantId)
            ->orderBy('expires_at', 'desc')
            ->first();

        $now = Carbon::now();
        $baseDate = $now;

        if ($latestKey && $latestKey->expires_at && $latestKey->expires_at->isFuture()) {
            // Append to the current expiration date
            $baseDate = $latestKey->expires_at;
        }

        $expiresAt = $baseDate->copy()->addMonths($durationInMonths);

        // 2. Generate a unique key string
        $keyString = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));

        // 3. Save the activation key
        ActivationKey::create([
            'key' => $keyString,
            'tenant_id' => $tenantId,
            'is_used' => false,
            'expires_at' => $expiresAt,
        ]);

        Log::info("Generated Activation Key for Tenant: {$tenantId}, Duration: {$durationInMonths} months.");
    }
}
