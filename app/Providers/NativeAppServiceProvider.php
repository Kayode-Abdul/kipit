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

        $dbPath = config('database.connections.sqlite.database');
        if (!file_exists($dbPath) || filesize($dbPath) === 0) {
            // Ensure the directory exists
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @touch($dbPath);
        }

        // Force SQLite connection for NativePHP to ignore any cached or .env MySQL settings
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => $dbPath]);
        config(['tenancy.database.central_connection' => 'sqlite']);
        
        // Purge connections so Laravel reconnects with the forced config
        \Illuminate\Support\Facades\DB::purge('sqlite');
        \Illuminate\Support\Facades\DB::purge('mysql');

        // Only migrate once to avoid slow downs and locks on every request
        $migratedFlag = $dbPath . '.migrated_v2';
        if (!file_exists($migratedFlag)) {
            $lockFile = $dbPath . '.lock';
            $lockHandle = @fopen($lockFile, 'w+');

            // Block all other NativePHP background threads until this process finishes migrating
            if ($lockHandle && flock($lockHandle, LOCK_EX)) {
                // Check again in case another process just migrated
                if (!file_exists($migratedFlag)) {
                    try {
                        Artisan::call('migrate', [
                            '--database' => 'sqlite',
                            '--path' => 'database/migrations',
                            '--force' => true,
                        ]);
                        \Illuminate\Support\Facades\Log::info("Central Migration Output: " . Artisan::output());
                        
                        Artisan::call('migrate', [
                            '--database' => 'sqlite',
                            '--path' => 'database/migrations/tenant',
                            '--force' => true,
                        ]);
                        \Illuminate\Support\Facades\Log::info("Tenant Migration Output: " . Artisan::output());
                        
                        @touch($migratedFlag);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Migration Failed: " . $e->getMessage());
                    }
                }
                
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }

        // Only open window if not running in console to prevent deadlocks during native:config
        if (class_exists(\Native\Laravel\Facades\Window::class) && !$this->app->runningInConsole()) {
            try {
                Window::open()
                    ->title('Kipit')
                    ->width(1280)
                    ->height(900)
                    ->minWidth(1024)
                    ->minHeight(768)
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
