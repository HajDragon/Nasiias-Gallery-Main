<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ContactMessageRequest;
use App\Jobs\SendContactMessage;
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

        foreach ($recipients as $recipientId) {
            SendContactMessage::dispatch(
                $recipientId,
                $request->validated('name'),
                $request->validated('email'),
                $request->validated('message'),
            );
        }

        return back();
    }
}
