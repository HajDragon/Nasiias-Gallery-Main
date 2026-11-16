<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Artwork;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewArtworkPublished extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Artwork $artwork) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New artwork: '.($this->artwork->title ?: 'Untitled artwork'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $imageUrl = $this->artwork->getFirstMediaUrl('images') ?: $this->artwork->image_url;

        if ($imageUrl !== '' && ! str_starts_with($imageUrl, 'http://') && ! str_starts_with($imageUrl, 'https://')) {
            $imageUrl = asset($imageUrl);
        }

        return new Content(
            view: 'emails.new-artwork',
            with: [
                'title' => $this->artwork->title ?: 'Untitled artwork',
                'description' => $this->artwork->description,
                'altText' => $this->artwork->alt_text ?: ($this->artwork->title ?: 'Artwork image'),
                'imageUrl' => $imageUrl !== '' ? $imageUrl : null,
                'url' => route('gallery.show', $this->artwork),
            ],
        );
    }
}
