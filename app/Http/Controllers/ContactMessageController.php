<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ContactMessageRequest;
use App\Jobs\SendContactMessage;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ContactMessageController extends Controller
{
    public function store(ContactMessageRequest $request): RedirectResponse
    {
        $recipients = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])
            ->where('notify_on_contact_message', true)
            ->pluck('id');

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'message' => __('We cannot receive messages right now. Please try again later.'),
            ]);
        }

        $message = ContactMessage::query()->create([
            'sender_name' => $request->validated('name'),
            'sender_email' => $request->validated('email'),
            'message' => $request->validated('message'),
        ]);

        foreach ($recipients as $recipientId) {
            SendContactMessage::dispatch(
                $recipientId,
                $message->sender_name,
                $message->sender_email,
                $message->message,
            );
        }

        return back();
    }
}
