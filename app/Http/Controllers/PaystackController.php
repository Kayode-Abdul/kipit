<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\PaystackActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaystackController extends Controller
{
    /**
     * Initialize Paystack checkout outside Livewire so shared-hosting
     * request timeouts (408) do not kill the Filament action mid-flight.
     */
    public function checkout(Request $request)
    {
        @set_time_limit(60);

        try {
            $planId = (int) $request->query('plan_id');
            $tenant = tenant();
            $user = Auth::guard('web')->user() ?? $request->user();

            if (! $tenant || ! $user) {
                return redirect('/member/activation')
                    ->with('error', __('You must be logged in to pay.'));
            }

            $secretKey = (string) (
                config('services.paystack.secret_key')
                ?: env('PAYSTACK_SECRET_KEY')
                ?: ($_ENV['PAYSTACK_SECRET_KEY'] ?? '')
            );

            if (trim($secretKey) === '') {
                Log::error('Paystack checkout failed: PAYSTACK_SECRET_KEY not set.');

                return redirect('/member/activation')
                    ->with('error', __('Paystack is not configured. Please contact support.'));
            }

            $central = (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));

            $plan = SubscriptionPlan::on($central)
                ->with('prices')
                ->where('is_published', true)
                ->find($planId);

            if (! $plan) {
                return redirect('/member/activation')
                    ->with('error', __('Please choose a published subscription plan.'));
            }

            $price = $plan->prices->firstWhere('currency_code', 'NGN')
                ?? $plan->prices->first();

            if (! $price) {
                return redirect('/member/activation')
                    ->with('error', __('This plan is missing a price. Please contact support.'));
            }

            $amount = (float) $price->price;
            $currency = strtoupper((string) $price->currency_code);

            $response = Http::withToken(trim($secretKey))
                ->timeout(12)
                ->connectTimeout(5)
                ->asJson()
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => (int) round($amount * 100),
                    'currency' => $currency,
                    'reference' => 'KIPIT_'.time().'_'.$tenant->getTenantKey(),
                    'callback_url' => url('/member/activation'),
                    'metadata' => [
                        'tenant_id' => $tenant->getTenantKey(),
                        'plan_id' => $plan->id,
                        'duration_in_months' => (int) $plan->duration_in_months,
                        'type' => 'subscription_purchase',
                    ],
                ]);

            $authorizationUrl = data_get($response->json(), 'data.authorization_url');

            if ($response->successful() && is_string($authorizationUrl) && $authorizationUrl !== '') {
                return redirect()->away($authorizationUrl);
            }

            Log::error('Paystack checkout rejected', [
                'tenant_id' => $tenant->getTenantKey(),
                'plan_id' => $plan->id,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $message = data_get($response->json(), 'message')
                ?? __('Please check your Paystack keys.');

            return redirect('/member/activation')->with('error', $message);
        } catch (Throwable $e) {
            Log::error('Paystack checkout crashed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Show the real error so shared-hosting debugging is possible.
            return response(
                '<h1>Paystack checkout error</h1><pre>'.e($e->getMessage()."\n".$e->getFile().':'.$e->getLine()).'</pre>'
                .'<p><a href="/member/activation">Back to activation</a></p>',
                500
            );
        }
    }

    /**
     * Handle Paystack Webhook
     */
    public function webhook(Request $request, PaystackActivationService $activationService)
    {
        $secretKey = (string) (
            config('services.paystack.secret_key')
            ?: env('PAYSTACK_SECRET_KEY')
            ?: ($_ENV['PAYSTACK_SECRET_KEY'] ?? '')
        );

        if (trim($secretKey) === '') {
            Log::error('Paystack webhook failed: PAYSTACK_SECRET_KEY not set.');

            return response()->json(['status' => 'error'], 500);
        }

        $signature = $request->header('x-paystack-signature');
        if (! $signature) {
            return response()->json(['status' => 'error', 'message' => 'Missing signature'], 400);
        }

        $input = $request->getContent();
        if ($signature !== hash_hmac('sha512', $input, trim($secretKey))) {
            Log::warning('Paystack webhook failed: Invalid signature.');

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $event = json_decode($input, true);
        if (($event['event'] ?? null) === 'charge.success') {
            $activationService->fulfillSuccessfulCharge($event['data'] ?? []);
        }

        return response()->json(['status' => 'success']);
    }
}
