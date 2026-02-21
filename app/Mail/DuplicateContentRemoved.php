<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification sent when a lesson plan is removed because
 * its file content is identical to an earlier upload.
 */
class DuplicateContentRemoved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $deletedPlanName,
        public string $keptPlanName,
        public string $keptAuthorName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Lesson Plan Was Removed (Duplicate Content)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.duplicate-content-removed',
        );
    }
}
