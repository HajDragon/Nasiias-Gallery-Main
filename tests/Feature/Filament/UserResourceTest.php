<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_browse_the_user_directory(): void
    {
        // Arrange
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $member = User::factory()->create(['display_name' => 'Mira Koval']);

        // Act
        $response = $this->actingAs($superAdmin)->get('/admin/users');

        // Assert
        $response->assertOk();
        $response->assertSee('Mira Koval');
        $response->assertSee($member->email);
    }

    public function test_admins_cannot_browse_or_edit_users(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $member = User::factory()->create();

        // Act & Assert
        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get("/admin/users/{$member->id}/edit")->assertForbidden();
    }

    public function test_super_admin_can_promote_a_user_to_admin_and_edit_their_profile(): void
    {
        // Arrange
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $member = User::factory()->create(['role' => UserRole::User]);

        // Act
        $this->actingAs($superAdmin);
        Livewire::test(EditUser::class, ['record' => $member->id])
            ->fillForm([
                'display_name' => 'Curator Nova',
                'email' => 'nova@example.com',
                'role' => UserRole::Admin->value,
                'notify_on_contact_message' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $member->refresh();
        $this->assertSame('Curator Nova', $member->display_name);
        $this->assertSame('nova@example.com', $member->email);
        $this->assertSame(UserRole::Admin, $member->role);
        $this->assertNull($member->email_verified_at);
    }

    public function test_super_admin_cannot_assign_the_super_admin_role(): void
    {
        // Arrange
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $member = User::factory()->create(['role' => UserRole::User]);

        // Act
        $this->actingAs($superAdmin);
        Livewire::test(EditUser::class, ['record' => $member->id])
            ->fillForm(['role' => UserRole::SuperAdmin->value])
            ->call('save');

        // Assert
        $this->assertSame(UserRole::User, $member->fresh()->role);
    }

    public function test_existing_super_admins_cannot_be_demoted_from_the_directory(): void
    {
        // Arrange
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $otherSuperAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        // Act
        $this->actingAs($superAdmin);
        Livewire::test(EditUser::class, ['record' => $otherSuperAdmin->id])
            ->fillForm(['display_name' => 'Renamed Super Admin'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $otherSuperAdmin->refresh();
        $this->assertSame('Renamed Super Admin', $otherSuperAdmin->display_name);
        $this->assertSame(UserRole::SuperAdmin, $otherSuperAdmin->role);
    }

    public function test_super_admin_cannot_change_their_own_role(): void
    {
        // Arrange
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        // Act
        $this->actingAs($superAdmin);
        Livewire::test(EditUser::class, ['record' => $superAdmin->id])
            ->fillForm(['display_name' => 'Gallery Owner'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert
        $superAdmin->refresh();
        $this->assertSame('Gallery Owner', $superAdmin->display_name);
        $this->assertSame(UserRole::SuperAdmin, $superAdmin->role);
    }
}
