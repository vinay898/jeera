<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\EmailConfiguration;
use App\Models\Ticket;
use App\Services\ImapService;
use App\Services\InboundEmailService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpImap\Exceptions\ConnectionException;

#[Signature('emails:process-inbound {--force : Process all configurations regardless of poll interval}')]
#[Description('Poll IMAP mailboxes for new emails and create tickets/comments')]
class ProcessInboundEmails extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImapService $imapService, InboundEmailService $emailService): int
    {
        $force = $this->option('force');

        // Get all active IMAP configurations
        $configurations = EmailConfiguration::query()
            ->where('is_active', true)
            ->where('imap_enabled', true)
            ->whereNotNull('imap_host')
            ->whereNotNull('imap_username')
            ->whereNotNull('imap_password')
            ->get();

        if ($configurations->isEmpty()) {
            $this->info('No active IMAP configurations found.');

            return self::SUCCESS;
        }

        $this->info("Found {$configurations->count()} IMAP configuration(s).");

        foreach ($configurations as $config) {
            // Check if due for polling (unless force flag is set)
            if (! $force && ! $config->isDueForPolling()) {
                $this->line("Skipping {$config->inbound_email} - not due for polling.");

                continue;
            }

            $this->processMailbox($config, $imapService, $emailService);
        }

        return self::SUCCESS;
    }

    /**
     * Process a single IMAP mailbox.
     */
    protected function processMailbox(
        EmailConfiguration $config,
        ImapService $imapService,
        InboundEmailService $emailService
    ): void {
        $this->info("Processing mailbox: {$config->imap_username}");

        try {
            $emails = $imapService->fetchUnreadEmails($config);

            if ($emails->isEmpty()) {
                $this->line('  No new emails found.');
                $this->updateLastPolled($config);

                return;
            }

            $this->info("  Found {$emails->count()} unread email(s).");

            foreach ($emails as $mail) {
                try {
                    $emailData = $imapService->parseEmail($mail);

                    $this->line("  Processing: {$emailData['subject']}");

                    $result = $emailService->processEmail($emailData, $config);

                    if ($result instanceof Ticket) {
                        $this->info("    -> Created ticket: {$result->key}");
                    } elseif ($result instanceof Comment) {
                        $this->info("    -> Added comment to ticket: {$result->ticket->key}");
                    } else {
                        $this->warn('    -> Email skipped (auto-create disabled or no matching ticket)');
                    }

                    // Mark email as read/processed
                    $imapService->markAsProcessed($mail->id);
                } catch (\Exception $e) {
                    $this->error("    -> Error processing email: {$e->getMessage()}");
                    Log::error('Failed to process email', [
                        'config_id' => $config->id,
                        'subject' => $mail->subject ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->updateLastPolled($config);
        } catch (ConnectionException $e) {
            $this->error("  Connection failed: {$e->getMessage()}");
            Log::error('IMAP connection failed', [
                'config_id' => $config->id,
                'host' => $config->imap_host,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->error("  Error: {$e->getMessage()}");
            Log::error('IMAP processing failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the last polled timestamp.
     */
    protected function updateLastPolled(EmailConfiguration $config): void
    {
        $config->update(['last_polled_at' => now()]);
    }
}
