<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtworkAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_artwork_crud_in_admin(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::User]);

        // Act & Assert: List
        $response = $this->actingAs($user)->get('/admin/artworks');
        $response->assertStatus(404);

        // Act & Assert: Create
        $response = $this->actingAs($user)->get('/admin/artworks/create');
        $response->assertStatus(404);

        // Act & Assert: Edit
        $artwork = Artwork::factory()->create();
        $response = $this->actingAs($user)->get("/admin/artworks/{$artwork->id}/edit");
        $response->assertStatus(404);
    }

    public function test_admin_can_manage_all_artworks(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();

        // Act & Assert: Create
        $response = $this->actingAs($admin)->get('/admin/artworks/create');
        $response->assertOk();

        // Act & Assert: List
        $response = $this->actingAs($admin)->get('/admin/artworks');
        $response->assertOk();

        // Act & Assert: Edit
        $artwork = Artwork::factory()->create(['category_id' => $category->id]);
        $response = $this->actingAs($admin)->get("/admin/artworks/{$artwork->id}/edit");
        $response->assertOk();
    }

    public function test_super_admin_has_full_access(): void
    {
        // Arrange
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        // Act
        $response = $this->actingAs($superAdmin)->get('/admin/artworks');

        // Assert
        $response->assertOk();
    }

    public function test_artwork_policy_enforced_on_delete(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $artwork = Artwork::factory()->create();

        // Act
        $response = $this->actingAs($admin)->delete("/admin/artworks/{$artwork->id}");

        // Assert: Policy allows admin to delete
        $this->assertTrue($admin->can('delete', $artwork));
    }

    public function test_regular_user_cannot_delete_artwork(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::User]);
        $artwork = Artwork::factory()->create();

        // Act: Regular user cannot access admin panel at all
        $response = $this->actingAs($user)->delete("/admin/artworks/{$artwork->id}");

        // Assert
        $response->assertStatus(404);
    }

    public function test_artwork_form_validation_enforced(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();

        // Act & Assert: Test title required
        $response = $this->actingAs($admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => '',
            'category_id' => $category->id,
            'description' => 'Test',
        ]);
        $response->assertSessionHasErrors('title');

        // Act & Assert: Test category required
        $response = $this->actingAs($admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => '',
            'description' => 'Test',
        ]);
        $response->assertSessionHasErrors('category_id');
    }
}
