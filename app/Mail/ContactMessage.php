<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message submitted through the public contact form.
 *
 * The recipient is passed in by the controller, which reads it from the edition
 * row. Putting the address in the constructor rather than in a `to()` on a
 * subclass is what stops the form from choosing where the mail goes.
 *
 * The submitter's address is the `replyTo`, not the sender. Using it as the
 * envelope sender would fail SPF/DMARC for this domain and, more importantly,
 * would make the message look like it came from the organiser.
 */
class ContactMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly ?string $subjectLine,
        public readonly string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine !== null && $this->subjectLine !== ''
                ? $this->subjectLine
                : __('contact.default_subject'),
            replyTo: [$this->senderEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact',
            text: 'mail.contact-text',
            with: [
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'subjectLine' => $this->subjectLine,
                'body' => $this->body,
            ],
        );
    }
}
