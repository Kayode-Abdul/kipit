<?php

namespace App\Http\Middleware;

use App\Helpers\DesktopHelper;
use Closure;
use Illuminate\Http\Request;

class DesktopSetupMiddleware
{
    /**
     * Handle an incoming request.
     * Redirects to the desktop setup wizard if the app is running in desktop mode
     * and setup has not been completed yet.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!DesktopHelper::isDesktopApp()) {
            return $next($request);
        }

        // Allow these routes through even if setup is not complete
        $isExcluded = $request->is('desktop/*')
            || $request->is('livewire/*')
            || $request->is('_debugbar/*')
            || $request->is('assets/*')
            || $request->is('vendor/*')
            || $request->is('build/*')
            || $request->is('favicon.ico');

        if ($isExcluded) {
            return $next($request);
        }

        if (!DesktopHelper::isSetupComplete()) {
            if (DesktopHelper::isFirstLaunch()) {
                return redirect()->route('desktop.welcome');
            }

            return redirect()->route('desktop.setup');
        }

        return $next($request);
    }
}
