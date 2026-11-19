<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super admin',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::User => 'gray',
            self::Admin => 'info',
            self::SuperAdmin => 'danger',
        };
    }
}
