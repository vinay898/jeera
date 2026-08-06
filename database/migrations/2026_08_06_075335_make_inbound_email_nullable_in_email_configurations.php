<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to change column constraints
        // First, make the column nullable using Schema::table with change()
        Schema::table('email_configurations', function (Blueprint $table) {
            $table->string('inbound_email')->nullable()->change();
        });

        // Now convert empty strings to NULL
        DB::table('email_configurations')
            ->where('inbound_email', '')
            ->update(['inbound_email' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert NULLs back to empty strings first
        DB::table('email_configurations')
            ->whereNull('inbound_email')
            ->update(['inbound_email' => '']);

        Schema::table('email_configurations', function (Blueprint $table) {
            $table->string('inbound_email')->nullable(false)->change();
        });
    }
};
