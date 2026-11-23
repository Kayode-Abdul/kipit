<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes for sync cursor scans and unsynced push pages.
     *
     * @var list<string>
     */
    protected array $tables = [
        'products',
        'members',
        'sellings',
        'selling_details',
        'stocks',
        'purchasings',
        'categories',
        'suppliers',
        'users',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'updated_at') && Schema::hasColumn($tableName, 'id')) {
                    $index = $tableName.'_updated_at_id_index';
                    if (! Schema::hasIndex($tableName, $index)) {
                        $table->index(['updated_at', 'id'], $index);
                    }
                }

                if (Schema::hasColumn($tableName, 'is_synced') && Schema::hasColumn($tableName, 'id')) {
                    $index = $tableName.'_is_synced_id_index';
                    if (! Schema::hasIndex($tableName, $index)) {
                        $table->index(['is_synced', 'id'], $index);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $updatedAtId = $tableName.'_updated_at_id_index';
                if (Schema::hasIndex($tableName, $updatedAtId)) {
                    $table->dropIndex($updatedAtId);
                }

                $isSyncedId = $tableName.'_is_synced_id_index';
                if (Schema::hasIndex($tableName, $isSyncedId)) {
                    $table->dropIndex($isSyncedId);
                }
            });
        }
    }
};
