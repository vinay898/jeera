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
        Schema::table('lovs', function (Blueprint $table) {
            $table->jsonb('metadata')->nullable()->change();
        });

        Schema::table('workflows', function (Blueprint $table) {
            $table->jsonb('statuses')->nullable()->change();
            $table->jsonb('transitions')->nullable()->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->jsonb('labels')->nullable()->change();
            $table->jsonb('custom_fields')->nullable()->change();
            $table->jsonb('watchers')->nullable()->change();
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->jsonb('options')->nullable()->change();
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->jsonb('references')->nullable()->change();
            $table->jsonb('to_addresses')->change();
            $table->jsonb('cc_addresses')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lovs', function (Blueprint $table) {
            $table->json('metadata')->nullable()->change();
        });

        Schema::table('workflows', function (Blueprint $table) {
            $table->json('statuses')->nullable()->change();
            $table->json('transitions')->nullable()->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->json('labels')->nullable()->change();
            $table->json('custom_fields')->nullable()->change();
            $table->json('watchers')->nullable()->change();
        });

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->json('options')->nullable()->change();
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->json('references')->nullable()->change();
            $table->json('to_addresses')->change();
            $table->json('cc_addresses')->nullable()->change();
        });
    }
};
