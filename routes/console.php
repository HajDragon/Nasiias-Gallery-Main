<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('role:assign {email} {role}', function (string $email, string $role) {
    $user = User::where('email', $email)->first();

    if ($user === null) {
        $this->components->error("No user found with email [{$email}]");

        return;
    }

    try {
        $userRole = UserRole::from($role);
    } catch (ValueError) {
        $this->components->error("Invalid role [{$role}]. Valid roles: user, admin, super_admin");

        return;
    }

    $user->update(['role' => $userRole]);

    $this->components->info("[{$user->email}] is now [{$userRole->value}]");
})->purpose('Assign a role (user, admin, super_admin) to a user by email');
