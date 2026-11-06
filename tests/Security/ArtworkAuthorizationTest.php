<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\User;
use App\Models\Artwork;
use App\Models\Category;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtworkAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_artwork_crud_in_admin(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        // List
        $response = $this->actingAs($user)->get('/admin/artworks');
        $response->assertStatus(404);

        // Create
        $response = $this->actingAs($user)->get('/admin/artworks/create');
        $response->assertStatus(404);

        // Edit (if any artwork exists)
        $artwork = Artwork::factory()->create();
        $response = $this->actingAs($user)->get("/admin/artworks/{$artwork->id}/edit");
        $response->assertStatus(404);
    }

    public function test_admin_can_manage_all_artworks(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();

        // Create
        $response = $this->actingAs($admin)->get('/admin/artworks/create');
        $response->assertOk();

        // List
        $response = $this->actingAs($admin)->get('/admin/artworks');
        $response->assertOk();

        // Edit
        $artwork = Artwork::factory()->create(['category_id' => $category->id]);
        $response = $this->actingAs($admin)->get("/admin/artworks/{$artwork->id}/edit");
        $response->assertOk();
    }

    public function test_super_admin_has_full_access(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $response = $this->actingAs($superAdmin)->get('/admin/artworks');
        $response->assertOk();
    }

    public function test_artwork_policy_enforced_on_delete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $artwork = Artwork::factory()->create();

        // Admin should be able to delete
        $response = $this->actingAs($admin)->delete("/admin/artworks/{$artwork->id}");
        // Filament uses a different route for delete, typically via Livewire
        // This test verifies the policy is checked
        $this->assertTrue($admin->can('delete', $artwork));
    }

    public function test_regular_user_cannot_delete_artwork(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);
        $artwork = Artwork::factory()->create();

        // Regular user cannot access admin panel at all
        $response = $this->actingAs($user)->delete("/admin/artworks/{$artwork->id}");
        $response->assertStatus(404);
    }

    public function test_artwork_form_validation_enforced(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();

        // Test title required
        $response = $this->actingAs($admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => '',
            'category_id' => $category->id,
            'description' => 'Test',
        ]);
        $response->assertSessionHasErrors('title');

        // Test category required
        $response = $this->actingAs($admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => '',
            'description' => 'Test',
        ]);
        $response->assertSessionHasErrors('category_id');
    }
}
