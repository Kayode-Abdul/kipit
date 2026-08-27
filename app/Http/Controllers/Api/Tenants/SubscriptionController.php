<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanPrice;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stancl\Tenancy\Facades\Tenancy;

class SubscriptionController extends Controller
{
    /**
     * Get a list of all published subscription plans.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::with('prices')->where('is_published', true)->get();

        return response()->json([
            'status' => 'success',
            'data' => $plans,
        ]);
    }

    /**
     * Initialize a checkout session with Paystack for a specific plan and currency.
     */
    public function checkout(Request $request, PaystackService $paystack): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'currency' => 'required|string|size:3',
            'email' => 'required|email',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        $priceObj = $plan->prices()->where('currency_code', strtoupper($request->currency))->first();

        if (!$priceObj) {
            return response()->json([
                'status' => 'error',
                'message' => 'This plan does not support the selected currency.',
            ], 400);
        }

        $tenant = tenant();

        // Pass important info to Paystack metadata to be used in webhook
        $metadata = [
            'tenant_id' => $tenant ? $tenant->id : null,
            'plan_id' => $plan->id,
            'duration_in_months' => $plan->duration_in_months,
            'type' => 'subscription_purchase'
        ];

        $checkout = $paystack->initializeTransaction(
            $request->email,
            (float) $priceObj->price,
            strtoupper($request->currency),
            $metadata
        );

        if (!$checkout) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize payment with Paystack.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'authorization_url' => $checkout['data']['authorization_url'],
                'access_code' => $checkout['data']['access_code'],
                'reference' => $checkout['data']['reference'],
            ]
        ]);
    }
}
