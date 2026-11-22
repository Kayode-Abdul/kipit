<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for 150k+ row workloads: POS filters, reports, and sync pull/push.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('name');
            $table->index('sku');
            $table->index('show');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index(['category_id', 'show']);
        });

        Schema::table('sellings', function (Blueprint $table) {
            $table->index('date');
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::table('selling_details', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['product_id', 'type', 'is_ready']);
            $table->index('updated_at');
        });

        Schema::table('purchasings', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['show']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['category_id', 'show']);
        });

        Schema::table('sellings', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('selling_details', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'type', 'is_ready']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('purchasings', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });
    }
};
