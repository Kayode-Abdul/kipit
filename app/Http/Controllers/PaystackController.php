<?php

namespace App\Http\Controllers;

use App\Models\ActivationKey;
use App\Models\Tenants\User;
use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackController extends Controller
{
    /**
     * Handle Paystack Webhook
     */
    public function webhook(Request $request)
    {
        // 1. Verify the signature
        $secretKey = env('PAYSTACK_SECRET_KEY');
        if (!$secretKey) {
            Log::error('Paystack webhook failed: PAYSTACK_SECRET_KEY not set.');
            return response()->json(['status' => 'error'], 500);
        }

        $signature = $request->header('x-paystack-signature');
        if (!$signature) {
            return response()->json(['status' => 'error', 'message' => 'Missing signature'], 400);
        }

        // Validate event origin
        $input = $request->getContent();
        if ($signature !== hash_hmac('sha512', $input, $secretKey)) {
            Log::warning('Paystack webhook failed: Invalid signature.');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $event = json_decode($input, true);

        // We only care about charge.success
        if ($event['event'] === 'charge.success') {
            $data = $event['data'];
            $metadata = $data['metadata'] ?? [];
            
            $tenantId = $metadata['tenant_id'] ?? null;
            
            if (!$tenantId) {
                Log::warning('Paystack webhook: Successful payment received, but missing tenant_id in metadata.');
                return response()->json(['status' => 'success']); // Paystack expects 200 OK so it doesn't retry
            }

            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                Log::warning("Paystack webhook: Tenant ID {$tenantId} not found.");
                return response()->json(['status' => 'success']);
            }

            $type = $metadata['type'] ?? 'activation_key';
            
            // Log the transaction
            \App\Models\Transaction::firstOrCreate([
                'reference' => $data['reference'] ?? Str::random(16),
            ], [
                'tenant_id' => $tenant->id,
                'amount' => ($data['amount'] ?? 0) / 100,
                'currency' => $data['currency'] ?? 'USD',
                'type' => $type,
                'status' => 'success',
            ]);

            if ($type === 'subscription_purchase') {
                // Determine how many months to add
                $months = $metadata['duration_in_months'] ?? 1;
                $keyStr = Str::random(16);
                
                if ($tenant) {
                    // Append to existing plan
                    $latestExpiry = ActivationKey::where('tenant_id', $tenant->id)->max('expires_at');
                    $baseDate = $latestExpiry ? max(now(), \Carbon\Carbon::parse($latestExpiry)) : now();
                    $expiresAt = $baseDate->addMonths((int)$months);
                } else {
                    $expiresAt = now()->addMonths((int)$months);
                }

                ActivationKey::create([
                    'key' => $keyStr,
                    'is_used' => $tenant ? true : false,
                    'tenant_id' => $tenant ? $tenant->id : null,
                    'expires_at' => $expiresAt,
                ]);

                if ($tenant) {
                    Log::info("Paystack webhook: Successfully added {$months} months subscription to tenant {$tenant->id} with key {$keyStr}");
                }
            } else {
                // Generate Key (Permanent)
                $keyStr = Str::random(16);
                
                ActivationKey::create([
                    'key' => $keyStr,
                    'is_used' => false,
                    'tenant_id' => $tenant ? $tenant->id : null,
                    'expires_at' => null, // Permanent
                ]);

                if ($tenant) {
                    Log::info("Paystack webhook: Successfully activated shop for tenant {$tenant->id} with permanent key {$keyStr}");
                }
            }

            // Send email to customer with the key
            $customerEmail = $event['data']['customer']['email'] ?? null;
            if ($customerEmail) {
                try {
                    $receiptData = [
                        'reference' => $data['reference'] ?? 'N/A',
                        'type' => $type === 'subscription_purchase' ? 'Subscription' : 'Access Key',
                        'amount' => price_format(($data['amount'] ?? 0) / 100, $data['currency'] ?? 'NGN'),
                        'activation_key' => $keyStr,
                        'is_used' => $tenant ? true : false,
                        'expires_at' => $expiresAt ?? null,
                    ];
                    \Illuminate\Support\Facades\Mail::to($customerEmail)->send(new \App\Mail\TransactionReceipt($receiptData));
                    Log::info("Paystack webhook: Sent activation key {$keyStr} to {$customerEmail}");
                } catch (\Exception $e) {
                    Log::error("Paystack webhook: Failed to send activation key to {$customerEmail}. Error: " . $e->getMessage());
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
