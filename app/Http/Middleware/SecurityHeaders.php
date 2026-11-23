<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add baseline browser security headers to every response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Allow the Payment Request API so Paystack checkout widgets are not blocked.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '0'); // modern browsers use CSP; legacy header can cause issues

        if (config('security.csp_enabled', true)) {
            $response->headers->set(
                'Content-Security-Policy',
                (string) config('security.csp', $this->defaultCsp())
            );
        }

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Long-cache hashed/static public assets when served through PHP.
        if ($request->isMethod('GET') && (
            $request->is('assets/*')
            || $request->is('build/assets/*')
            || $request->is('favicon.ico')
            || $request->is('icon.png')
        )) {
            $response->headers->set('Cache-Control', 'public, max-age=604800, immutable');
        }

        return $response;
    }

    /**
     * Filament/Livewire-friendly CSP that still blocks most drive-by XSS sinks.
     *
     * Must allow Filament CDN fonts (bunny), TinyMCE (jsdelivr), and Paystack checkout.
     */
    protected function defaultCsp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self' https://checkout.paystack.com https://api.paystack.co https://standard.paystack.co https://js.paystack.co",
            "frame-ancestors 'self'",
            "frame-src 'self' https://checkout.paystack.com https://standard.paystack.co https://js.paystack.co https://api.paystack.co",
            "object-src 'none'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net https://js.paystack.co https://checkout.paystack.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://stackpath.bootstrapcdn.com https://cdn.jsdelivr.net",
            "font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com https://stackpath.bootstrapcdn.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https: wss:",
            "media-src 'self' blob: data:",
            "worker-src 'self' blob:",
        ]);
    }
}
