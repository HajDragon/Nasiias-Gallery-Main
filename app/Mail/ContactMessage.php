<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessage extends Mailable
{
    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: __('emails.contact.subject', ['name' => $this->senderName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
            with: [
                'subject' => __('emails.contact.subject', ['name' => $this->senderName]),
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
                'eyebrow' => __('emails.contact.eyebrow'),
                'heading' => __('emails.contact.heading'),
                'intro' => __('emails.contact.intro', ['app' => config('app.name')]),
                'fromLabel' => __('emails.contact.from'),
                'messageLabel' => __('emails.contact.message'),
                'replyLabel' => __('emails.contact.reply', ['name' => $this->senderName]),
                'footer' => __('emails.contact.footer', ['app' => config('app.name')]),
            ],
        );
    }
}
