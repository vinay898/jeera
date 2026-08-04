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
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('trigger_type');
            $table->unsignedInteger('trigger_minutes');
            $table->string('priority')->nullable();
            $table->foreignId('escalate_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('escalate_to_role')->nullable();
            $table->boolean('notify_assignee')->default(true);
            $table->boolean('notify_reporter')->default(false);
            $table->boolean('notify_watchers')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};
