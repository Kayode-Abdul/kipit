<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Leave csp null to use the Filament/Livewire-friendly default built into
    | SecurityHeaders middleware. Set csp_enabled=false if a page breaks.
    |
    */

    'csp_enabled' => (bool) env('SECURITY_CSP_ENABLED', true),

    'csp' => env('SECURITY_CSP'),

];
