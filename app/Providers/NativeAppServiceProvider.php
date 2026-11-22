<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Laravel\Facades\Window;
use Native\Laravel\Contracts\ProvidesPhpIni;
use Illuminate\Support\Facades\Artisan;

class NativeAppServiceProvider extends ServiceProvider implements ProvidesPhpIni
{
    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);
    }
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        if (!\App\Helpers\DesktopHelper::isDesktopApp()) {
            return;
        }

        $dbConnection = config('database.default');
        $dbPath = config("database.connections.{$dbConnection}.database");
        
        if (!file_exists($dbPath) || filesize($dbPath) === 0) {
            // Ensure the directory exists
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @touch($dbPath);
        }

        // Make sure Tenancy is using the correct connection (NativePHP's default)
        config(['tenancy.database.central_connection' => $dbConnection]);
        
        // Purge connections to ensure any changes are picked up
        \Illuminate\Support\Facades\DB::purge($dbConnection);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::purge('sqlite');

        // Optional SQLCipher unlock when PHP SQLite is compiled with SQLCipher.
        app(\App\Services\DesktopSqliteCipher::class)->applyKeyIfAvailable();

        // Re-run pending migrations when the installed app version changes (or on first boot).
        // Laravel's migrations table keeps this additive — shop SQLite data is preserved.
        $currentVersion = '0';
        $versionFile = base_path('version.txt');
        if (is_file($versionFile)) {
            $currentVersion = trim((string) file_get_contents($versionFile));
            if ($currentVersion === '') {
                $currentVersion = '0';
            }
        }

        $versionFlagPath = $dbPath.'.migrated_version';
        $lastMigratedVersion = is_file($versionFlagPath)
            ? trim((string) file_get_contents($versionFlagPath))
            : '';

        // Legacy one-shot flag from older builds — treat as unknown version so we migrate once.
        $legacyFlag = $dbPath.'.migrated_v3';
        if ($lastMigratedVersion === '' && is_file($legacyFlag)) {
            $lastMigratedVersion = 'legacy';
        }

        if ($lastMigratedVersion !== $currentVersion) {
            $lockFile = $dbPath.'.lock';
            $lockHandle = @fopen($lockFile, 'w+');

            if ($lockHandle && flock($lockHandle, LOCK_EX)) {
                $latestVersion = is_file($versionFlagPath)
                    ? trim((string) file_get_contents($versionFlagPath))
                    : '';

                if ($latestVersion !== $currentVersion) {
                    try {
                        Artisan::call('migrate', [
                            '--database' => $dbConnection,
                            '--path' => 'database/migrations',
                            '--force' => true,
                        ]);
                        \Illuminate\Support\Facades\Log::info('Desktop central migrate: '.Artisan::output());

                        Artisan::call('migrate', [
                            '--database' => $dbConnection,
                            '--path' => 'database/migrations/tenant',
                            '--force' => true,
                        ]);
                        \Illuminate\Support\Facades\Log::info('Desktop tenant migrate: '.Artisan::output());

                        // Schema/RBAC migrations change protected tables — refresh the
                        // integrity fingerprint so upgrade does not trigger cloud-wins
                        // purge of legitimate (including unsynced) shop data.
                        try {
                            $integrity = app(\App\Services\DatabaseIntegrityService::class);
                            $integrity->refreshChecksum();
                            $integrity->clearCloudWinsRepair();
                            \Illuminate\Support\Facades\Log::info('Desktop integrity checksum refreshed after migrate.');
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Post-migrate integrity refresh failed: '.$e->getMessage());
                        }

                        file_put_contents($versionFlagPath, $currentVersion);
                        @unlink($legacyFlag);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Desktop migration failed: '.$e->getMessage());
                    }
                }

                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }

        // Log environment for debugging
        \Illuminate\Support\Facades\Log::info('NATIVEPHP ENV:', collect($_SERVER)->filter(fn($v, $k) => str_starts_with($k, 'NATIVEPHP'))->toArray());

        // Open the NativePHP window. NativePHP's Window facade safely ignores subsequent calls if already open.
        if (!app()->runningInConsole() && class_exists(\Native\Laravel\Facades\Window::class)) {
            // Fix for IPv6 localhost resolution causing cURL error 7 on Windows
            if (PHP_OS_FAMILY === 'Windows' && config('nativephp-internal.api_url')) {
                config(['nativephp-internal.api_url' => str_replace('localhost', '127.0.0.1', config('nativephp-internal.api_url'))]);
            }
            
            try {
                Window::open()
                    ->title('Kipit')
                    ->width(1280)
                    ->height(900)
                    ->minWidth(1024)
                    ->minHeight(768)
                    ->showDevTools(false)
                    ->rememberState();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('NativePHP Window Open Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
