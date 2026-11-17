<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Filament\Resources\Artworks\Pages\EditArtwork;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use App\Rules\ValidArtworkImagePayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\Concerns\BuildsEditedPhotoPayloads;
use Tests\TestCase;

/**
 * The admin photo editor replaces the plain file upload: the edited bitmap
 * arrives as a base64 data URI instead of an uploaded file.
 */
class ArtworkPhotoEditorTest extends TestCase
{
    use BuildsEditedPhotoPayloads;
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->category = Category::factory()->create();
    }

    /**
     * @param  mixed  $value
     */
    private function passesPayloadRule($value, ?string $unchangedUrl = null): bool
    {
        return Validator::make(
            ['image' => $value],
            ['image' => [new ValidArtworkImagePayload(unchangedUrl: $unchangedUrl)]],
        )->passes();
    }

    public function test_edited_photo_payload_is_accepted(): void
    {
        $this->assertTrue($this->passesPayloadRule($this->editedPhotoPayload()));
    }

    public function test_stored_photo_path_is_accepted(): void
    {
        Storage::disk('public')->put('artworks/existing.webp', $this->editedPhotoBytes(width: 400, height: 300));

        $this->assertTrue($this->passesPayloadRule('artworks/existing.webp'));
    }

    public function test_an_unchanged_remote_photo_is_accepted_but_a_new_one_is_not(): void
    {
        $current = 'https://images.example.com/photo.jpg?w=1200';

        $this->assertTrue($this->passesPayloadRule($current, $current));
        $this->assertFalse($this->passesPayloadRule($current));
        $this->assertFalse($this->passesPayloadRule('https://evil.example.com/shell.php', $current));
    }

    public function test_php_payload_disguised_as_an_edited_photo_is_rejected(): void
    {
        $payload = 'data:image/webp;base64,'.base64_encode('<?php system($_GET["cmd"]); ?>');

        $this->assertFalse($this->passesPayloadRule($payload));
    }

    public function test_non_image_bytes_on_the_public_disk_are_rejected(): void
    {
        Storage::disk('public')->put('artworks/evil.webp', '<?php phpinfo(); ?>');

        $this->assertFalse($this->passesPayloadRule('artworks/evil.webp'));
    }

    public function test_path_traversal_is_rejected(): void
    {
        Storage::disk('public')->put('secret.webp', $this->editedPhotoBytes(width: 400, height: 300));

        $this->assertFalse($this->passesPayloadRule('../secret.webp'));
        $this->assertFalse($this->passesPayloadRule('artworks/../../secret.webp'));
    }

    public function test_a_payload_that_is_not_a_file_or_string_is_rejected(): void
    {
        $this->assertFalse($this->passesPayloadRule(['artworks/existing.webp']));
    }

    public function test_livewire_upload_objects_are_delegated_to_the_file_rule(): void
    {
        $this->assertTrue(
            $this->passesPayloadRule(UploadedFile::fake()->image('photo.webp', 200, 200)),
        );

        $this->assertFalse(
            $this->passesPayloadRule(
                UploadedFile::fake()->createWithContent('shell.webp', '<?php phpinfo(); ?>'),
            ),
        );
    }

    public function test_creating_an_artwork_stores_the_edited_photo(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateArtwork::class)
            ->fillForm([
                'title' => 'Edited Atelier Still Life',
                'description' => 'Cropped and straightened in the admin editor.',
                'category_id' => $this->category->id,
                'file' => $this->editedPhotoPayload(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $artwork = Artwork::query()->where('title', 'Edited Atelier Still Life')->firstOrFail();
        $media = $artwork->getFirstMedia('images');

        $this->assertNotNull($media);
        $this->assertStringEndsWith('.webp', (string) $media->file_name);
        $this->assertStringContainsString('/storage/', (string) $artwork->image_url);
        $this->assertFileExists(Storage::disk('public')->path($media->getPathRelativeToRoot()));
    }

    public function test_creating_an_artwork_with_a_disguised_payload_is_rejected(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateArtwork::class)
            ->fillForm([
                'title' => 'Trojan Horse',
                'description' => 'Not actually an image.',
                'category_id' => $this->category->id,
                'file' => 'data:image/webp;base64,'.base64_encode('<?php system($_GET["cmd"]); ?>'),
            ])
            ->call('create')
            ->assertHasFormErrors(['file']);

        $this->assertDatabaseCount('artworks', 0);
    }

    public function test_edit_page_prefills_the_published_photo_for_editing(): void
    {
        $artwork = $this->artworkWithMedia();

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->assertFormSet([
                'file' => $artwork->getFirstMedia('images')?->getPathRelativeToRoot(),
            ]);
    }

    public function test_edit_page_renders_the_published_photo_in_the_editor(): void
    {
        $artwork = $this->artworkWithMedia();

        $this->actingAs($this->admin)
            ->get("/admin/artworks/{$artwork->getRouteKey()}/edit")
            ->assertOk()
            ->assertSee('imageUrl')
            ->assertSee('published.webp');
    }

    public function test_edit_page_resolves_a_stored_image_url_to_its_disk_path(): void
    {
        Storage::disk('public')->put('legacy/piece.webp', $this->editedPhotoBytes(width: 400, height: 300));

        $artwork = $this->artworkWithoutMedia(Storage::disk('public')->url('legacy/piece.webp'));

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->assertFormSet(['file' => 'legacy/piece.webp']);
    }

    public function test_saving_an_untouched_legacy_photo_does_not_create_media(): void
    {
        Storage::disk('public')->put('legacy/piece.webp', $this->editedPhotoBytes(width: 400, height: 300));

        $url = Storage::disk('public')->url('legacy/piece.webp');
        $artwork = $this->artworkWithoutMedia($url);

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $artwork->refresh();

        $this->assertSame(0, $artwork->media()->count());
        $this->assertSame($url, $artwork->image_url);
    }

    public function test_edit_page_shows_a_remote_photo_and_keeps_it_on_save(): void
    {
        $url = 'https://images.example.com/photo.jpg?w=1200';

        $artwork = $this->artworkWithoutMedia($url);

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->assertFormSet(['file' => $url])
            ->call('save')
            ->assertHasNoFormErrors();

        $artwork->refresh();

        $this->assertSame($url, $artwork->image_url);
        $this->assertSame(0, $artwork->media()->count());
    }

    public function test_removing_a_remote_photo_clears_the_url(): void
    {
        $artwork = $this->artworkWithoutMedia('https://images.example.com/photo.jpg');

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->fillForm(['file' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('', $artwork->fresh()->image_url);
    }

    public function test_removing_the_published_photo_deletes_it_on_save(): void
    {
        $artwork = $this->artworkWithMedia();
        $path = (string) $artwork->getFirstMedia('images')?->getPathRelativeToRoot();

        $this->assertNotSame('', $artwork->image_url);

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->fillForm(['file' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $artwork->refresh();

        $this->assertSame(0, $artwork->media()->count());
        $this->assertSame('', $artwork->image_url);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_saving_an_untouched_photo_does_not_duplicate_the_media(): void
    {
        $artwork = $this->artworkWithMedia();

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, $artwork->fresh()->media()->count());
    }

    public function test_editing_the_published_photo_replaces_the_media(): void
    {
        $artwork = $this->artworkWithMedia();

        $this->actingAs($this->admin);

        Livewire::test(EditArtwork::class, ['record' => $artwork->getRouteKey()])
            ->fillForm([
                'file' => $this->editedPhotoPayload(width: 1600, height: 1000),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $artwork->refresh();

        $this->assertSame(1, $artwork->media()->count());
        $this->assertStringEndsWith('.webp', (string) $artwork->getFirstMedia('images')?->file_name);
    }

    public function test_edit_page_loads_the_editor_assets(): void
    {
        $artwork = $this->artworkWithMedia();

        $this->actingAs($this->admin)
            ->get("/admin/artworks/{$artwork->getRouteKey()}/edit")
            ->assertOk()
            ->assertSee('filament-image-editor')
            ->assertSee('image-editor-component', false);
    }

    private function artworkWithoutMedia(string $imageUrl): Artwork
    {
        return Artwork::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Seeded Piece',
            'image_url' => $imageUrl,
        ]);
    }

    private function artworkWithMedia(): Artwork
    {
        $artwork = Artwork::factory()->create([
            'category_id' => $this->category->id,
            'title' => 'Published Piece',
        ]);

        $artwork->addMediaFromString($this->editedPhotoBytes(width: 400, height: 300))
            ->usingFileName('published.webp')
            ->toMediaCollection('images');

        $artwork->update(['image_url' => (string) $artwork->getFirstMediaUrl('images')]);

        return $artwork;
    }
}
