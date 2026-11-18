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
        $title = $this->artwork->title ?: __('emails.new_artwork.untitled');

        return new Envelope(
            subject: __('emails.new_artwork.subject', ['title' => $title]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $imageUrl = (string) ($this->artwork->getFirstMediaUrl('images') ?: $this->artwork->image_url);

        if ($imageUrl !== '' && ! str_starts_with($imageUrl, 'http://') && ! str_starts_with($imageUrl, 'https://')) {
            $imageUrl = asset($imageUrl);
        }

        $title = $this->artwork->title ?: __('emails.new_artwork.untitled');

        return new Content(
            view: 'emails.new-artwork',
            with: [
                'title' => $title,
                'subject' => __('emails.new_artwork.subject', ['title' => $title]),
                'description' => $this->artwork->description,
                'altText' => $this->artwork->alt_text ?: $title,
                'imageUrl' => $imageUrl !== '' ? $imageUrl : null,
                'url' => route('gallery.show', $this->artwork),
                'viewLabel' => __('emails.new_artwork.view'),
                'footer' => __('emails.new_artwork.footer', ['app' => config('app.name')]),
            ],
        );
    }
}
