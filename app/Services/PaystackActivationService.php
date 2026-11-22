<?php

namespace App\Services;

use App\Mail\TransactionReceipt;
use App\Models\ActivationKey;
use App\Models\Transaction;
use App\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class PaystackActivationService
{
    /**
     * Verify a Paystack reference and activate the shop (idempotent).
     *
     * @return array{ok:bool,message:string,activation_key?:string}
     */
    public function fulfillByReference(string $reference): array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return ['ok' => false, 'message' => 'Missing payment reference.'];
        }

        // Already processed?
        $existing = Transaction::query()->where('reference', $reference)->first();
        if ($existing && $existing->status === 'success') {
            $key = ActivationKey::query()
                ->where('tenant_id', $existing->tenant_id)
                ->where('is_used', true)
                ->latest('id')
                ->value('key');

            return [
                'ok' => true,
                'message' => 'Payment was already processed.',
                'activation_key' => $key ? (string) $key : null,
            ];
        }

        $payload = $this->verifyReference($reference);
        if (! $payload) {
            return ['ok' => false, 'message' => 'Could not verify payment with Paystack.'];
        }

        $status = data_get($payload, 'data.status');
        if ($status !== 'success') {
            return ['ok' => false, 'message' => 'Payment has not been completed yet.'];
        }

        return $this->fulfillSuccessfulCharge(data_get($payload, 'data', []));
    }

    /**
     * Apply a successful Paystack charge payload (from verify or webhook).
     *
     * @param  array<string, mixed>  $data
     * @return array{ok:bool,message:string,activation_key?:string}
     */
    public function fulfillSuccessfulCharge(array $data): array
    {
        $reference = (string) ($data['reference'] ?? '');
        if ($reference === '') {
            return ['ok' => false, 'message' => 'Missing payment reference.'];
        }

        $existing = Transaction::query()->where('reference', $reference)->first();
        if ($existing && $existing->status === 'success') {
            return [
                'ok' => true,
                'message' => 'Payment was already processed.',
            ];
        }

        $metadata = $this->normalizeMetadata($data['metadata'] ?? []);
        $tenantId = $metadata['tenant_id'] ?? null;
        $type = $metadata['type'] ?? 'activation_key';
        $months = (int) ($metadata['duration_in_months'] ?? 1);
        if ($months < 1) {
            $months = 1;
        }

        $tenant = $tenantId ? Tenant::query()->find($tenantId) : null;

        $expiresAt = null;
        $keyStr = strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4));

        if ($type === 'subscription_purchase') {
            if ($tenant) {
                $latestExpiry = ActivationKey::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereNotNull('expires_at')
                    ->max('expires_at');

                $baseDate = now();
                if ($latestExpiry) {
                    $parsed = Carbon::parse($latestExpiry);
                    if ($parsed->isFuture()) {
                        $baseDate = $parsed;
                    }
                }

                $expiresAt = $baseDate->copy()->addMonths($months);
            } else {
                $expiresAt = now()->addMonths($months);
            }

            ActivationKey::create([
                'key' => $keyStr,
                'is_used' => (bool) $tenant,
                'tenant_id' => $tenant?->id,
                'expires_at' => $expiresAt,
            ]);
        } else {
            ActivationKey::create([
                'key' => $keyStr,
                'is_used' => false,
                'tenant_id' => $tenant?->id,
                'expires_at' => null,
            ]);
        }

        Transaction::query()->updateOrCreate(
            ['reference' => $reference],
            [
                'tenant_id' => $tenant?->id,
                'amount' => ((float) ($data['amount'] ?? 0)) / 100,
                'currency' => $data['currency'] ?? 'NGN',
                'type' => $type,
                'status' => 'success',
            ]
        );

        $customerEmail = data_get($data, 'customer.email');
        if (is_string($customerEmail) && $customerEmail !== '') {
            try {
                Mail::to($customerEmail)->send(new TransactionReceipt([
                    'reference' => $reference,
                    'type' => $type === 'subscription_purchase' ? 'Subscription' : 'Access Key',
                    'amount' => price_format(((float) ($data['amount'] ?? 0)) / 100, $data['currency'] ?? 'NGN'),
                    'activation_key' => $keyStr,
                    'is_used' => (bool) $tenant,
                    'expires_at' => $expiresAt,
                ]));
            } catch (Throwable $e) {
                Log::error('Paystack activation email failed', [
                    'email' => $customerEmail,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Paystack payment fulfilled', [
            'reference' => $reference,
            'tenant_id' => $tenant?->id,
            'type' => $type,
            'key' => $keyStr,
        ]);

        return [
            'ok' => true,
            'message' => $tenant
                ? 'Payment successful. Your shop has been activated.'
                : 'Payment successful. Your activation key is ready.',
            'activation_key' => $keyStr,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function verifyReference(string $reference): ?array
    {
        $secretKey = (string) (
            config('services.paystack.secret_key')
            ?: env('PAYSTACK_SECRET_KEY')
            ?: ($_ENV['PAYSTACK_SECRET_KEY'] ?? '')
        );

        if (trim($secretKey) === '') {
            Log::error('Paystack verify failed: secret key missing');

            return null;
        }

        try {
            $response = Http::withToken(trim($secretKey))
                ->timeout(15)
                ->connectTimeout(5)
                ->get('https://api.paystack.co/transaction/verify/'.urlencode($reference));

            if (! $response->successful()) {
                Log::error('Paystack verify rejected', [
                    'reference' => $reference,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::error('Paystack verify request failed', [
                'reference' => $reference,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Paystack may return metadata as assoc array or custom_fields list.
     *
     * @param  mixed  $metadata
     * @return array<string, mixed>
     */
    protected function normalizeMetadata(mixed $metadata): array
    {
        if (! is_array($metadata)) {
            return [];
        }

        if (isset($metadata['custom_fields']) && is_array($metadata['custom_fields'])) {
            foreach ($metadata['custom_fields'] as $field) {
                if (! is_array($field)) {
                    continue;
                }
                $name = $field['variable_name'] ?? $field['display_name'] ?? null;
                if (is_string($name) && $name !== '' && array_key_exists('value', $field)) {
                    $metadata[$name] = $field['value'];
                }
            }
        }

        return $metadata;
    }
}
