<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation email sent to the uploader when a lesson plan is
 * successfully stored (new plan or new version).
 */
class LessonPlanUploaded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $canonicalFilename,
        public string $className,
        public int    $lessonDay,
        public string $semanticVersion,
        public string $viewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Lesson Plan Was Uploaded Successfully',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lesson-plan-uploaded',
        );
    }
}
