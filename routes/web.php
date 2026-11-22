<?php

use App\Livewire\Forms\Auth\RegisterTenantForm;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

use Illuminate\Support\Facades\Artisan;

// Shared-hosting ops routes — require MAINTENANCE_TOKEN (?token= or X-Maintenance-Token)
Route::middleware('maintenance.token')->group(function () {
    Route::get('/__migrate', function () {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $output = [];
        $failed = false;

        try {
            // Central (landlord) migrations
            Artisan::call('migrate', ['--force' => true]);
            $output[] = "=== Central migrate ===\n".Artisan::output();
        } catch (\Throwable $e) {
            $failed = true;
            $output[] = "=== Central migrate ERROR ===\n".$e->getMessage();
        }

        // Migrate each tenant separately so one missing DB cannot block the rest.
        foreach (\App\Tenant::query()->cursor() as $tenant) {
            $tenantKey = (string) $tenant->getTenantKey();
            $dbName = $tenant->database()->getName();

            try {
                $manager = $tenant->database()->manager();

                if (! $manager->databaseExists($dbName)) {
                    $output[] = "=== Tenant {$tenantKey}: creating missing database {$dbName} ===";
                    $tenant->database()->makeCredentials();
                    $manager->createDatabase($tenant);
                }

                Artisan::call('tenants:migrate', [
                    '--tenants' => [$tenantKey],
                    '--force' => true,
                ]);
                $output[] = "=== Tenant {$tenantKey} migrate ({$dbName}) ===\n".Artisan::output();
            } catch (\Throwable $e) {
                $failed = true;
                $output[] = "=== Tenant {$tenantKey} ERROR ({$dbName}) ===\n".$e->getMessage();
            }
        }

        $status = $failed ? 500 : 200;

        return response('<pre>'.e(implode("\n\n", $output)).'</pre>', $status);
    });

    Route::get('/__backup', function () {
        try {
            Artisan::call('backup:run');

            return '<pre>'.e(Artisan::output()).'</pre>';
        } catch (\Throwable $e) {
            return response('<pre>'.e($e->getMessage()).'</pre>', 500);
        }
    });

    Route::get('/__queue', function () {
        try {
            // Processes jobs and stops immediately when empty (perfect for hitting via a web cron job)
            Artisan::call('queue:work', ['--stop-when-empty' => true]);

            return '<pre>'.e(Artisan::output()).'</pre>';
        } catch (\Throwable $e) {
            return response('<pre>'.e($e->getMessage()).'</pre>', 500);
        }
    });

    Route::get('/clear-cache', function () {
        try {
            Artisan::call('package:discover', ['--ansi' => false]);
            Artisan::call('optimize:clear');

            return '<pre>'.e(Artisan::output()).'</pre>';
        } catch (\Throwable $e) {
            return response('<pre>'.e($e->getMessage()).'</pre>', 500);
        }
    });

    Route::get('/__paystack-ping', function () {
        $secret = (string) config('services.paystack.secret_key', '');
        $lines = [
            'secret_configured='.($secret !== '' ? 'yes' : 'no'),
            'secret_prefix='.($secret !== '' ? substr($secret, 0, 7).'...' : '(empty)'),
        ];

        try {
            $started = microtime(true);
            $response = \Illuminate\Support\Facades\Http::withToken($secret)
                ->timeout(8)
                ->connectTimeout(4)
                ->get('https://api.paystack.co/bank?perPage=1');
            $ms = (int) round((microtime(true) - $started) * 1000);
            $lines[] = 'http_status='.$response->status();
            $lines[] = 'latency_ms='.$ms;
            $lines[] = 'body_snippet='.substr($response->body(), 0, 200);
        } catch (\Throwable $e) {
            $lines[] = 'error='.$e->getMessage();
        }

        return '<pre>'.e(implode("\n", $lines)).'</pre>';
    });
});
if (\App\Helpers\DesktopHelper::isDesktopApp()) {
    Route::get('/', function () {
        return redirect()->route('desktop.splash');
    });

    Route::get('/storage/{path}', function (string $path) {
        $uploadDisk = config('filesystems.upload_disk', 'public');
        $candidates = [
            \Illuminate\Support\Facades\Storage::disk($uploadDisk)->path($path),
            public_path('storage/'.$path),
            storage_path('app/public/'.$path),
        ];

        $fullPath = null;
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && file_exists($candidate)) {
                $fullPath = $candidate;
                break;
            }
        }

        if (! $fullPath) {
            abort(404);
        }

        $contents = \App\Services\FileEncryptionService::getDecryptedContents($fullPath);
        if ($contents === '') {
            abort(404);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($contents) ?: 'application/octet-stream';

        return response($contents, 200)->header('Content-Type', $mime);
    })->where('path', '.*');
} else {
    Volt::route('/', 'pages/welcome');
}
Volt::route('/download', 'pages/download')->name('download');
Volt::route('/pricing', 'pages/pricing')->name('pricing');

// Legal & Support static pages
Route::view('/privacy', 'pages.legal.privacy')->name('privacy');
Route::view('/terms', 'pages.legal.terms')->name('terms');
Route::view('/cookies', 'pages.legal.cookies')->name('cookies');
Route::view('/security', 'pages.legal.security')->name('security');
Route::view('/getting-started', 'pages.support.getting-started')->name('getting-started');
Route::view('/help', 'pages.support.help')->name('help');
Route::view('/api-docs', 'pages.support.api')->name('api-docs');
Volt::route('/contact', 'pages/support/contact')->name('contact');
Volt::route('/blog', 'pages/blog/index')->name('blog.index');
Volt::route('/blog/{slug}', 'pages/blog/show')->name('blog.show');
Route::view('/status', 'pages.support.status')->name('status');
Route::view('/offline', 'offline');

Route::get('/serviceworker.js', function () {
    return response()->file(public_path('serviceworker.js'))
        ->header('Content-Type', 'application/javascript');
});

Route::get('/auth/register', RegisterTenantForm::class)
    ->name('auth.register');

Route::post('/paystack/webhook', [\App\Http\Controllers\PaystackController::class, 'webhook'])
    ->name('paystack.webhook')
    ->middleware('throttle:webhook');

Route::middleware([
    'web',
])
    ->prefix('admin')
    ->group(function () {
        //
    });


