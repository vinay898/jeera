<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\EmailConfiguration;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\IncomingMail;
use PhpImap\Mailbox;

class ImapService
{
    private ?Mailbox $mailbox = null;

    /**
     * Connect to an IMAP mailbox.
     *
     * @throws ConnectionException
     */
    public function connect(EmailConfiguration $config): Mailbox
    {
        $this->mailbox = new Mailbox(
            $config->getImapConnectionString(),
            $config->imap_username,
            $config->imap_password,
            storage_path('app/attachments'),
            'UTF-8'
        );

        return $this->mailbox;
    }

    /**
     * Test connection to the IMAP server.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws ConnectionException
     */
    public function testConnection(array $config): bool
    {
        $encryption = match ($config['imap_encryption'] ?? 'ssl') {
            'ssl' => '/ssl',
            'tls' => '/tls',
            default => '',
        };

        $connectionString = sprintf(
            '{%s:%d/imap%s}%s',
            $config['imap_host'],
            $config['imap_port'] ?? 993,
            $encryption,
            $config['imap_mailbox'] ?? 'INBOX'
        );

        $mailbox = new Mailbox(
            $connectionString,
            $config['imap_username'],
            $config['imap_password'],
            storage_path('app/attachments'),
            'UTF-8'
        );

        // This will throw ConnectionException if connection fails
        $mailbox->getMailboxInfo();

        return true;
    }

    /**
     * Fetch unread emails from the mailbox.
     *
     * @return Collection<int, IncomingMail>
     *
     * @throws ConnectionException
     */
    public function fetchUnreadEmails(EmailConfiguration $config): Collection
    {
        $mailbox = $this->connect($config);

        $mailIds = $mailbox->searchMailbox('UNSEEN');

        $emails = collect();

        foreach ($mailIds as $mailId) {
            $mail = $mailbox->getMail($mailId);
            $emails->push($mail);
        }

        return $emails;
    }

    /**
     * Mark an email as read.
     */
    public function markAsRead(int $mailId): void
    {
        if ($this->mailbox) {
            $this->mailbox->markMailAsRead($mailId);
        }
    }

    /**
     * Mark an email as processed by moving it to a folder or flagging it.
     */
    public function markAsProcessed(int $mailId): void
    {
        if ($this->mailbox) {
            $this->mailbox->markMailAsRead($mailId);
        }
    }

    /**
     * Parse email data into a structured array.
     *
     * @return array<string, mixed>
     */
    public function parseEmail(IncomingMail $mail): array
    {
        return [
            'message_id' => $mail->messageId,
            'in_reply_to' => $mail->inReplyTo,
            'references' => $mail->references,
            'from_email' => $mail->fromAddress,
            'from_name' => $mail->fromName,
            'to_addresses' => $mail->to,
            'cc_addresses' => $mail->cc,
            'subject' => $mail->subject,
            'body_text' => $mail->textPlain,
            'body_html' => $mail->textHtml,
            'date' => $mail->date,
            'attachments' => $this->parseAttachments($mail),
        ];
    }

    /**
     * Parse email attachments.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseAttachments(IncomingMail $mail): array
    {
        $attachments = [];

        foreach ($mail->getAttachments() as $attachment) {
            $attachments[] = [
                'name' => $attachment->name,
                'file_path' => $attachment->filePath,
                'mime_type' => $attachment->mime,
                'size' => $attachment->fileSize ?? 0,
            ];
        }

        return $attachments;
    }

    /**
     * Find an existing ticket by email message ID or in-reply-to header.
     */
    public function findExistingTicket(?string $messageId, ?string $inReplyTo): ?Ticket
    {
        if (! $inReplyTo) {
            return null;
        }

        // First try to find by the in-reply-to header
        $ticket = Ticket::where('email_message_id', $inReplyTo)->first();

        if ($ticket) {
            return $ticket;
        }

        // Also check comments for email_message_id
        $comment = Comment::where('email_message_id', $inReplyTo)->first();

        if ($comment) {
            return $comment->ticket;
        }

        return null;
    }

    /**
     * Extract ticket key from email subject.
     * Looks for patterns like [PROJECT-123] or (PROJECT-123).
     */
    public function extractTicketKeyFromSubject(string $subject): ?string
    {
        if (preg_match('/[\[\(]([A-Z]+-\d+)[\]\)]/', $subject, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
