<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendContactMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public int $recipientId,
        public string $senderName,
        public string $senderEmail,
        public string $messageBody,
    ) {}

    public function handle(): void
    {
        $recipient = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])
            ->where('notify_on_contact_message', true)
            ->find($this->recipientId);

        if ($recipient === null) {
            return;
        }

        Mail::to($recipient)
            ->locale($recipient->locale ?? config('app.fallback_locale'))
            ->send(new ContactMessage(
                $this->senderName, $this->senderEmail, $this->messageBody,
            ));
    }
}
