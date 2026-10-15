<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Resources\Artworks\Pages\EditArtwork;
use App\Filament\Resources\Artworks\RelationManagers\CommentsRelationManager;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArtworkResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_access_artwork_create_page(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/artworks/create');

        // Assert
        $response->assertOk();
    }

    public function test_admin_can_upload_artwork_via_form(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'Abstract']);
        $file = UploadedFile::fake()->image('vermilion-weather.jpg', 1200, 800);

        // Act
        $this->actingAs($admin);
        Livewire::test(CreateArtwork::class)
            ->fillForm([
                'title' => 'Vermilion Weather',
                'description' => 'A storm held mid-flash in reds and yellows.',
                'category_id' => $category->id,
                'file' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert
        $this->assertDatabaseHas('artworks', [
            'title' => 'Vermilion Weather',
            'description' => 'A storm held mid-flash in reds and yellows.',
            'category_id' => $category->id,
            'alt_text' => 'Vermilion Weather',
        ]);

        $artwork = Artwork::where('title', 'Vermilion Weather')->first();
        $this->assertNotNull($artwork);
        $this->assertTrue($artwork->hasMedia('images'));
    }

    public function test_artwork_image_is_visible_in_cms_table(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'Geometric']);
        $file = UploadedFile::fake()->image('shape-of-memory.jpg', 800, 600);

        $artwork = Artwork::factory()->create([
            'category_id' => $category->id,
            'title' => 'The Shape of Memory',
        ]);
        $artwork->addMedia($file)->toMediaCollection('images');
        $artwork->update(['image_url' => $artwork->getFirstMediaUrl('images')]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/artworks');

        // Assert
        $response->assertOk();
        $response->assertSee('shape-of-memory');
    }

    public function test_current_artwork_image_is_visible_when_editing(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create(['name' => 'Minimalism']);
        $file = UploadedFile::fake()->image('quiet-current.jpg', 800, 600);

        $artwork = Artwork::factory()->create([
            'category_id' => $category->id,
            'title' => 'A Quiet Current',
        ]);
        $artwork->addMedia($file)->toMediaCollection('images');
        $artwork->update(['image_url' => $artwork->getFirstMediaUrl('images')]);

        // Act
        $response = $this->actingAs($admin)->get("/admin/artworks/{$artwork->id}/edit");

        // Assert
        $response->assertOk();
        $response->assertSee('Artwork Showcase');
        $response->assertSee('quiet-current');
    }

    public function test_admin_can_see_artwork_comments_when_editing(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['display_name' => 'ArtEnthusiast']);
        $category = Category::factory()->create();
        $artwork = Artwork::factory()->create([
            'category_id' => $category->id,
            'title' => 'In Bloom',
        ]);
        $comment = Comment::factory()->create([
            'artwork_id' => $artwork->id,
            'user_id' => $user->id,
            'body' => 'Stunning brushwork and vibrant tones!',
        ]);

        // Act & Assert
        Livewire::actingAs($admin)
            ->test(CommentsRelationManager::class, [
                'ownerRecord' => $artwork,
                'pageClass' => EditArtwork::class,
            ])
            ->assertCanSeeTableRecords([$comment])
            ->assertSee('ArtEnthusiast')
            ->assertSee('Stunning brushwork and vibrant tones!');
    }

    public function test_admin_panel_renders_custom_styling_and_font_assets(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Act
        $response = $this->actingAs($admin)->get('/admin');

        // Assert
        $response->assertOk();
        $response->assertSee('/css/nastia.css');
    }
}
