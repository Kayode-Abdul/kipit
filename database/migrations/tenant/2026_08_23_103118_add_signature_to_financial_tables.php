<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sellings', function (Blueprint $table) {
            $table->string('signature')->nullable();
        });

        Schema::table('selling_details', function (Blueprint $table) {
            $table->string('signature')->nullable();
        });

        Schema::table('purchasings', function (Blueprint $table) {
            $table->string('signature')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellings', function (Blueprint $table) {
            $table->dropColumn('signature');
        });

        Schema::table('selling_details', function (Blueprint $table) {
            $table->dropColumn('signature');
        });

        Schema::table('purchasings', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
