<?php

namespace App\Providers;

use App\Models\Tenants\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, User::class);

        // Send Filament password-reset emails immediately (no queue worker required).
        $this->app->bind(
            \Filament\Notifications\Auth\ResetPassword::class,
            \App\Notifications\Auth\FilamentResetPassword::class
        );

        // Conditionally load the Desktop App Provider ONLY if NativePHP is installed
        if (class_exists(\Native\Laravel\NativeServiceProvider::class)) {
            $this->app->register(\App\Providers\NativeAppServiceProvider::class);
        }

        if ($this->app->environment('local', 'development') && class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('filter', function (Request $request) {
            /* WIP:  <07-08-22, sheenazien8> */
            $columns = $request->filters;
            $query = $this;
            if ($columns) {
                foreach ($columns as $filterColumn) {
                    $column = $filterColumn['column'];

                    if ($filterColumn['condition'] == 'equals') {
                        $condition = '=';
                    } else {
                        $condition = $filterColumn['condition'];
                    }
                    if ($filterColumn['condition'] == 'like') {
                        $value = '%'.$filterColumn['value'].'%';
                    } else {
                        $value = $filterColumn['value'];
                    }
                    if (! $value) {
                        return $this;
                    }
                    $query = optional($this)->where($column, $condition, $value);
                }
            }

            return $columns ? $query : $this;
        });
        if (! config('tenancy.central_domains')[0]) {
            $mainPath = database_path('migrations');
            $directories = glob($mainPath.'/*', GLOB_ONLYDIR);

            $this->loadMigrationsFrom($directories);
        }

        Feature::resolveScopeUsing(fn ($driver) => null);
        Feature::discover();

        // Desktop: keep uploaded media in the writable userData storage path
        // (app bundle public/storage is not reliable for sync'd product images).
        if (\App\Helpers\DesktopHelper::isDesktopApp()) {
            $nativeStorage = getenv('NATIVEPHP_STORAGE_PATH') ?: env('NATIVEPHP_STORAGE_PATH');
            if (is_string($nativeStorage) && $nativeStorage !== '') {
                $publicRoot = rtrim($nativeStorage, DIRECTORY_SEPARATOR)
                    .DIRECTORY_SEPARATOR.'app'
                    .DIRECTORY_SEPARATOR.'public';

                if (! is_dir($publicRoot)) {
                    @mkdir($publicRoot, 0755, true);
                }

                config([
                    'filesystems.disks.public.root' => $publicRoot,
                    'filesystems.disks.public.url' => rtrim((string) config('app.url'), '/').'/storage',
                ]);
            }
        }
    }
}
