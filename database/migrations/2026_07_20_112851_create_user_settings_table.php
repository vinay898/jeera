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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('compact_mode')->default(false);
            $table->string('font_size')->default('medium');
            $table->unsignedInteger('items_per_page')->default(25);
            $table->string('theme')->default('system');
            $table->string('accent_color')->default('blue');
            $table->boolean('sidebar_collapsed')->default(false);
            $table->string('default_ticket_view')->default('list');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
