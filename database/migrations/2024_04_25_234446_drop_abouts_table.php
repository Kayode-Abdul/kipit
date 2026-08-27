<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isDesktop = \App\Helpers\DesktopHelper::isDesktopApp() || config('database.default') === 'sqlite';
        if (Schema::hasTable('abouts') && !$isDesktop) {
            Artisan::call('tenants:run app:move-about-to-tenant');
        }
        if (!empty(config('tenancy.central_domains'))) {
            Schema::dropIfExists('abouts');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
