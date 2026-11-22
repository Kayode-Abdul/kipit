<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Livewire sends X-CSRF-TOKEN itself — do not exclude livewire/* or CSRF is bypassed for the whole UI.
        'paystack/webhook',
    ];
}
