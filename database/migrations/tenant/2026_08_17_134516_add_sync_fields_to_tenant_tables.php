<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'users', 'categories', 'products', 'members', 'sellings', 'selling_details',
        'stocks', 'suppliers', 'purchasings', 'stock_opnames', 'stock_opname_items',
        'receivables', 'receivable_items', 'receivable_payments', 'cash_drawers',
        'cart_items'
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'sync_id')) {
                        $table->uuid('sync_id')->nullable()->unique();
                    }
                    if (!Schema::hasColumn($tableName, 'is_synced')) {
                        $table->boolean('is_synced')->default(false)->index();
                    }
                });

                // Populate missing sync_ids for existing records
                \Illuminate\Support\Facades\DB::table($tableName)->whereNull('sync_id')->orderBy('id')->chunkById(500, function ($records) use ($tableName) {
                    foreach ($records as $record) {
                        \Illuminate\Support\Facades\DB::table($tableName)
                            ->where('id', $record->id)
                            ->update(['sync_id' => \Illuminate\Support\Str::uuid()->toString()]);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_tables', function (Blueprint $table) {
            //
        });
    }
};
