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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->boolean('email_ticket_assigned')->default(true);
            $table->boolean('email_ticket_commented')->default(true);
            $table->boolean('email_ticket_mentioned')->default(true);
            $table->boolean('email_ticket_status_changed')->default(true);
            $table->boolean('email_ticket_closed')->default(true);
            $table->boolean('email_sla_warning')->default(true);
            $table->boolean('email_sla_breach')->default(true);
            $table->boolean('email_daily_digest')->default(false);
            $table->time('digest_time')->nullable();
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
