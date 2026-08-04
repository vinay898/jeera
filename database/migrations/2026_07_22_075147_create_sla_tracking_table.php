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
        Schema::create('sla_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('sla_configuration_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('response_deadline_at')->nullable();
            $table->timestamp('resolution_deadline_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('response_breached')->default(false);
            $table->boolean('resolution_breached')->default(false);
            $table->boolean('response_warning_sent')->default(false);
            $table->boolean('resolution_warning_sent')->default(false);
            $table->timestamp('paused_at')->nullable();
            $table->unsignedInteger('paused_duration_minutes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_tracking');
    }
};
