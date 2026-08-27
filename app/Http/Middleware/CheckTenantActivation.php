<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTenantActivation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (tenant() !== null) {
            $tenant = tenant();

            if (!$tenant->isActive()) {
                // If it is an API request, return a 403 JSON response
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Your free trial has expired or you do not have an active activation key. Please activate your shop.',
                        'trial_expired' => true,
                    ], 403);
                }

                // Check if current request is for the activation page, logout, or asset files
                $isExcluded = $request->routeIs('filament.tenant.pages.activation')
                    || $request->is('*/activation*')
                    || $request->is('*/logout*')
                    || $request->is('livewire/*')
                    || $request->is('_debugbar/*');

                if (!$isExcluded) {
                    return redirect()->to('/member/activation');
                }
            }
        }

        return $next($request);
    }
}
