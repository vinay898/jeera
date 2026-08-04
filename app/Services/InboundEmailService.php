<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailDirection;
use App\Enums\EmailStatus;
use App\Enums\TicketSource;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\EmailConfiguration;
use App\Models\EmailLog;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InboundEmailService
{
    public function __construct(
        private ImapService $imapService
    ) {}

    /**
     * Process an email and create a ticket or comment.
     *
     * @param  array<string, mixed>  $emailData
     */
    public function processEmail(array $emailData, EmailConfiguration $config): Ticket|Comment|null
    {
        // Check if this is a reply to an existing ticket
        $existingTicket = $this->imapService->findExistingTicket(
            $emailData['message_id'] ?? null,
            $emailData['in_reply_to'] ?? null
        );

        // Also try to find ticket by key in subject
        if (! $existingTicket && isset($emailData['subject'])) {
            $ticketKey = $this->imapService->extractTicketKeyFromSubject($emailData['subject']);
            if ($ticketKey) {
                $existingTicket = Ticket::where('key', $ticketKey)->first();
            }
        }

        if ($existingTicket) {
            return $this->createCommentFromEmail($emailData, $existingTicket);
        }

        if ($config->auto_create_tickets) {
            return $this->createTicketFromEmail($emailData, $config);
        }

        Log::info('Email received but auto-create is disabled', [
            'from' => $emailData['from_email'],
            'subject' => $emailData['subject'],
        ]);

        return null;
    }

    /**
     * Create a ticket from an email.
     *
     * @param  array<string, mixed>  $emailData
     */
    public function createTicketFromEmail(array $emailData, EmailConfiguration $config): Ticket
    {
        return DB::transaction(function () use ($emailData, $config) {
            // Find or identify the user
            $user = $this->findOrCreateUserFromEmail($emailData['from_email'], $emailData['from_name']);

            // Generate ticket key
            $project = $config->project;
            $ticketKey = $this->generateTicketKey($project);

            // Create the ticket
            $ticket = Ticket::create([
                'team_id' => $config->team_id,
                'project_id' => $config->project_id,
                'key' => $ticketKey,
                'title' => $this->cleanSubject($emailData['subject'] ?? 'No Subject'),
                'description' => $emailData['body_html'] ?: $emailData['body_text'] ?: '',
                'type' => $config->default_ticket_type,
                'priority' => $config->default_priority,
                'reporter_id' => $user?->id,
                'source' => TicketSource::Email,
                'external_email' => $emailData['from_email'],
                'email_message_id' => $emailData['message_id'],
            ]);

            // Log the inbound email
            $this->logEmail($emailData, $config, $ticket);

            // Process attachments
            if (! empty($emailData['attachments'])) {
                $this->processAttachments($emailData['attachments'], $ticket);
            }

            Log::info('Ticket created from email', [
                'ticket_id' => $ticket->id,
                'ticket_key' => $ticket->key,
                'from' => $emailData['from_email'],
            ]);

            return $ticket;
        });
    }

    /**
     * Create a comment on a ticket from an email.
     *
     * @param  array<string, mixed>  $emailData
     */
    public function createCommentFromEmail(array $emailData, Ticket $ticket): Comment
    {
        return DB::transaction(function () use ($emailData, $ticket) {
            // Find the user
            $user = $this->findOrCreateUserFromEmail($emailData['from_email'], $emailData['from_name']);

            // Check if comment is from external user (customer)
            $isInternal = false;
            if ($user && $ticket->team->users->contains($user)) {
                // User is a team member, might be internal
                $isInternal = false;
            }

            // Create the comment
            $comment = Comment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user?->id,
                'body' => $emailData['body_html'] ?: $emailData['body_text'] ?: '',
                'is_internal' => $isInternal,
                'source' => TicketSource::Email,
                'email_message_id' => $emailData['message_id'],
            ]);

            // Log the inbound email
            $this->logEmail($emailData, $ticket->team->emailConfiguration, $ticket, $comment);

            Log::info('Comment created from email', [
                'ticket_id' => $ticket->id,
                'comment_id' => $comment->id,
                'from' => $emailData['from_email'],
            ]);

            return $comment;
        });
    }

    /**
     * Find or create a user from an email address.
     */
    protected function findOrCreateUserFromEmail(string $email, ?string $_name): ?User
    {
        // First try to find existing user
        $user = User::where('email', $email)->first();

        if ($user) {
            return $user;
        }

        // For now, just return null for external users
        // In a production system, you might want to create a "contact" or guest user
        // using the $_name parameter
        return null;
    }

    /**
     * Generate a ticket key for the project.
     */
    protected function generateTicketKey(Project $project): string
    {
        $lastTicket = Ticket::where('project_id', $project->id)
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($lastTicket && preg_match('/-(\d+)$/', $lastTicket->key, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return $project->key.'-'.$nextNumber;
    }

    /**
     * Clean the email subject (remove Re:, Fwd:, etc.).
     */
    protected function cleanSubject(string $subject): string
    {
        // Remove common prefixes
        $subject = preg_replace('/^(Re:|Fwd:|FW:|RE:|AW:)\s*/i', '', $subject);

        // Remove ticket key if present (we'll use our own)
        $subject = preg_replace('/^\s*[\[\(][A-Z]+-\d+[\]\)]\s*/', '', $subject);

        return trim($subject) ?: 'No Subject';
    }

    /**
     * Log an inbound email.
     *
     * @param  array<string, mixed>  $emailData
     */
    protected function logEmail(
        array $emailData,
        ?EmailConfiguration $_config,
        Ticket $ticket,
        ?Comment $comment = null
    ): EmailLog {
        return EmailLog::create([
            'team_id' => $ticket->team_id,
            'ticket_id' => $ticket->id,
            'comment_id' => $comment?->id,
            'direction' => EmailDirection::Inbound,
            'message_id' => $emailData['message_id'],
            'in_reply_to' => $emailData['in_reply_to'],
            'references' => $emailData['references'] ?? [],
            'from_address' => $emailData['from_email'],
            'to_addresses' => array_keys($emailData['to_addresses'] ?? []),
            'cc_addresses' => array_keys($emailData['cc_addresses'] ?? []),
            'subject' => $emailData['subject'],
            'body_text' => $emailData['body_text'],
            'body_html' => $emailData['body_html'],
            'status' => EmailStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Process and save email attachments.
     *
     * @param  array<int, array<string, mixed>>  $attachments
     */
    protected function processAttachments(array $attachments, Ticket $ticket): void
    {
        foreach ($attachments as $attachment) {
            if (! isset($attachment['file_path']) || ! file_exists($attachment['file_path'])) {
                continue;
            }

            $filename = $attachment['name'] ?? 'attachment';
            // Use consistent directory pattern with existing AttachmentsRelationManager
            $storagePath = 'attachments/tickets/'.Str::uuid().'_'.$filename;

            // Move file to storage (using public disk explicitly)
            Storage::disk('public')->put($storagePath, file_get_contents($attachment['file_path']));

            // Create attachment record with correct field names
            Attachment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $ticket->reporter_id,
                'filename' => $filename,
                'path' => $storagePath,
                'size' => $attachment['size'] ?? 0,
                'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream',
            ]);

            // Clean up temp file
            @unlink($attachment['file_path']);
        }
    }
}
