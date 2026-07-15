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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('epic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 20)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('task');
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('story_points')->nullable();
            $table->unsignedInteger('original_estimate')->nullable();
            $table->unsignedInteger('time_spent')->default(0);
            $table->unsignedInteger('time_remaining')->nullable();
            $table->date('due_date')->nullable();
            $table->string('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('labels')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('watchers')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'project_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
