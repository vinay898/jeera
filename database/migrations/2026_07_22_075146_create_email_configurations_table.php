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
        Schema::create('email_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('inbound_email')->unique();
            $table->string('inbound_secret');
            $table->string('from_name');
            $table->string('from_address');
            $table->text('signature')->nullable();
            $table->boolean('auto_create_tickets')->default(true);
            $table->string('default_ticket_type')->default('task');
            $table->string('default_priority')->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_configurations');
    }
};
