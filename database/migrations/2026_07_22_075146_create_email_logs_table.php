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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');
            $table->string('message_id')->index();
            $table->string('in_reply_to')->nullable();
            $table->json('references')->nullable();
            $table->string('from_address');
            $table->json('to_addresses');
            $table->json('cc_addresses')->nullable();
            $table->string('subject');
            $table->text('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('status');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
