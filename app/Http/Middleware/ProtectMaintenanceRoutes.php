<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectMaintenanceRoutes
{
    /**
     * Require a non-empty MAINTENANCE_TOKEN query/header match.
     * Routes stay unavailable until the token is configured.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = $this->expectedToken();

        if ($expected === '') {
            abort(
                Response::HTTP_FORBIDDEN,
                'Maintenance routes are disabled. Set MAINTENANCE_TOKEN in the app root .env (next to artisan), confirm config/app.php has maintenance_token, then delete bootstrap/cache/config.php if it exists.'
            );
        }

        $provided = trim((string) ($request->query('token')
            ?? $request->header('X-Maintenance-Token')
            ?? ''));

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid maintenance token.');
        }

        return $next($request);
    }

    /**
     * Resolve token from config first, then raw environment (shared-hosting safe).
     */
    protected function expectedToken(): string
    {
        $candidates = [
            config('app.maintenance_token'),
            env('MAINTENANCE_TOKEN'),
            $_ENV['MAINTENANCE_TOKEN'] ?? null,
            $_SERVER['MAINTENANCE_TOKEN'] ?? null,
            getenv('MAINTENANCE_TOKEN') ?: null,
        ];

        foreach ($candidates as $value) {
            if (! is_string($value)) {
                continue;
            }

            $token = trim($value);
            if ($token !== '') {
                return $token;
            }
        }

        return '';
    }
}
