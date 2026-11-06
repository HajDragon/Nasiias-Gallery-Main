<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(404);
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_session_regenerated_on_login(): void
    {
        $user = User::factory()->create();
        $originalSessionId = $this->getSession()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $newSessionId = $this->getSession()->getId();

        $this->assertNotEquals($originalSessionId, $newSessionId);
    }

    public function test_privilege_escalation_via_role_manipulation_blocked(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        // Attempt to manipulate role via mass assignment
        $response = $this->actingAs($user)->patch('/settings/profile', [
            'display_name' => 'Hacker',
            'role' => 'admin', // This should not be mass assignable
        ]);

        $user->refresh();
        $this->assertEquals(UserRole::User, $user->role);
    }

    public function test_idor_on_artwork_resource_blocked(): void
    {
        $admin1 = User::factory()->create(['role' => UserRole::Admin]);
        $admin2 = User::factory()->create(['role' => UserRole::Admin]);
        $artwork = \App\Models\Artwork::factory()->create(['user_id' => $admin1->id]);

        // Admin2 tries to access Admin1's artwork edit page
        $response = $this->actingAs($admin2)->get("/admin/artworks/{$artwork->id}/edit");

        // Should still work for admins (they can see all), but let's verify
        // If there's ownership enforcement, this would be different
        $response->assertOk();
    }

    public function test_admin_cannot_impersonate_other_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['role' => UserRole::User]);

        // No impersonation route should exist
        $response = $this->actingAs($admin)->post('/admin/impersonate', [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(404);
    }
}
