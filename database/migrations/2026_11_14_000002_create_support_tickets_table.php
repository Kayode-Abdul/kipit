<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('tenant_user_id')->nullable()->index();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->string('category', 64)->default('general')->index();
            $table->string('subject');
            $table->text('body');
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 16)->default('normal')->index();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('author_type', 32); // admin | tenant_user
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_name');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
