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
        Schema::table('email_configurations', function (Blueprint $table) {
            $table->string('imap_host')->nullable()->after('is_active');
            $table->integer('imap_port')->default(993)->after('imap_host');
            $table->string('imap_encryption')->default('ssl')->after('imap_port');
            $table->string('imap_username')->nullable()->after('imap_encryption');
            $table->text('imap_password')->nullable()->after('imap_username');
            $table->string('imap_mailbox')->default('INBOX')->after('imap_password');
            $table->integer('poll_interval_seconds')->default(60)->after('imap_mailbox');
            $table->timestamp('last_polled_at')->nullable()->after('poll_interval_seconds');
            $table->boolean('imap_enabled')->default(false)->after('last_polled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'imap_host',
                'imap_port',
                'imap_encryption',
                'imap_username',
                'imap_password',
                'imap_mailbox',
                'poll_interval_seconds',
                'last_polled_at',
                'imap_enabled',
            ]);
        });
    }
};
