<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailAddress extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $verificationUrl, public User $user)
    {
        $this->to($user->email, $user->display_name);
        $this->locale($user->locale ?? app()->getLocale());
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.verify.subject', ['app' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'subject' => __('emails.verify.subject', ['app' => config('app.name')]),
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
                'greeting' => __('emails.verify.greeting', ['name' => $this->user->display_name]),
                'intro' => __('emails.verify.intro', ['app' => config('app.name')]),
                'actionLabel' => __('emails.verify.action'),
                'url' => $this->verificationUrl,
                'outro' => __('emails.verify.outro'),
                'footer' => __('emails.verify.footer', ['app' => config('app.name')]),
            ],
        );
    }
}
