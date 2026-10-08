<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

/**
 * Double opt-in e-mail, written in the language the person subscribed in
 * (stored in `consent_locale`, the same wording they agreed to on the site).
 */
class NewsletterConfirmation extends Mailable
{
    public function __construct(public NewsletterSubscriber $subscriber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: trans('newsletter.mail_subject', [], $this->subscriber->consent_locale),
        );
    }

    public function content(): Content
    {
        $locale = $this->subscriber->consent_locale;

        return new Content(
            view: 'emails.newsletter-confirm',
            with: [
                'locale' => $locale,
                'dir'    => $locale === 'ar' ? 'rtl' : 'ltr',
                'url'    => URL::temporarySignedRoute(
                    'newsletter.confirm',
                    now()->addDays(3),
                    ['subscriber' => $this->subscriber->getKey()]
                ),
            ],
        );
    }
}