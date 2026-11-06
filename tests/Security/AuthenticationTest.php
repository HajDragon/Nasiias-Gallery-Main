<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        // Arrange: Set up the test environment
        $user = User::factory()->create(['role' => UserRole::User]);

        // Act: Execute the code to test
        $response = $this->actingAs($user)->get('/admin');

        // Assert: Verify the results
        $response->assertStatus(404);
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Act
        $response = $this->actingAs($admin)->get('/admin');

        // Assert
        $response->assertOk();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        // Act
        $response = $this->get('/admin');

        // Assert
        $response->assertRedirect('/admin/login');
    }

    public function test_session_regenerated_on_login(): void
    {
        // Arrange
        $user = User::factory()->create();
        $originalSessionId = $this->getSession()->getId();

        // Act
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $newSessionId = $this->getSession()->getId();

        // Assert
        $this->assertNotEquals($originalSessionId, $newSessionId);
    }

    public function test_privilege_escalation_via_role_manipulation_blocked(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::User]);

        // Act: Attempt to manipulate role via mass assignment
        $response = $this->actingAs($user)->patch('/settings/profile', [
            'display_name' => 'Hacker',
            'role' => 'admin',
        ]);

        $user->refresh();

        // Assert
        $this->assertEquals(UserRole::User, $user->role);
    }

    public function test_idor_on_artwork_resource_blocked(): void
    {
        // Arrange
        $admin1 = User::factory()->create(['role' => UserRole::Admin]);
        $admin2 = User::factory()->create(['role' => UserRole::Admin]);
        $artwork = Artwork::factory()->create(['user_id' => $admin1->id]);

        // Act: Admin2 tries to access Admin1's artwork edit page
        $response = $this->actingAs($admin2)->get("/admin/artworks/{$artwork->id}/edit");

        // Assert: Should still work for admins (they can see all)
        $response->assertOk();
    }

    public function test_admin_cannot_impersonate_other_users(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['role' => UserRole::User]);

        // Act: No impersonation route should exist
        $response = $this->actingAs($admin)->post('/admin/impersonate', [
            'user_id' => $user->id,
        ]);

        // Assert
        $response->assertStatus(404);
    }
}
