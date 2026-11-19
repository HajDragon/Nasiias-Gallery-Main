<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Only super admins may browse the user directory.
     */
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Only super admins may inspect another user's account.
     */
    public function view(User $user, User $model): bool
    {
        return $this->isSuperAdmin($user);
    }

    /**
     * Only super admins may change another user's account.
     */
    public function update(User $user, User $model): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
