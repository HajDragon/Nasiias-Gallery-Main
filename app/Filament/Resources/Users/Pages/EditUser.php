<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        /** @var User $record */
        $record = $this->getRecord();

        return 'Edit '.$record->getFilamentName();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        // Changing an email address invalidates its verification, matching
        // the behaviour of the public profile settings screen.
        if (($data['email'] ?? null) !== $record->email) {
            $data['email_verified_at'] = null;
        }

        // Never let this screen promote anyone to super admin, demote a
        // super admin, or let an operator change their own role.
        if (
            UserResource::cannotChangeRole($record)
            || ($data['role'] ?? null) === UserRole::SuperAdmin->value
        ) {
            unset($data['role']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $emailChanged = array_key_exists('email_verified_at', $data);
        unset($data['email_verified_at']);

        $record->fill($data);

        // The verified timestamp is not mass assignable, so it is reset
        // explicitly when an operator changes the email address.
        if ($emailChanged) {
            $record->email_verified_at = null;
        }

        $record->save();

        $this->ensureOperatorRemainsSuperAdmin($record);

        return $record;
    }

    /**
     * Guard against an operator locking themselves out of the panel.
     */
    private function ensureOperatorRemainsSuperAdmin(User $record): void
    {
        $operator = Auth::user();

        if (! $operator instanceof User || ! $operator->is($record)) {
            return;
        }

        if ($record->role !== UserRole::SuperAdmin) {
            $record->forceFill(['role' => UserRole::SuperAdmin])->save();
        }
    }
}
