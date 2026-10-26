<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Models\Category;
use App\Models\User;
use App\Rules\ValidArtworkImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class AdminArtworkUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function passesValidation(UploadedFile $file): bool
    {
        return Validator::make(
            ['image' => $file],
            ['image' => [new ValidArtworkImage]],
        )->passes();
    }

    public function test_genuine_jpeg_image_passes_validation(): void
    {
        $this->assertTrue(
            $this->passesValidation(UploadedFile::fake()->image('painting.jpg', 800, 600)),
        );
    }

    public function test_genuine_png_image_passes_validation(): void
    {
        $this->assertTrue(
            $this->passesValidation(UploadedFile::fake()->image('drawing.png', 400, 400)),
        );
    }

    public function test_php_script_disguised_as_image_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'shell.jpg',
            '<?php system($_GET["cmd"]); ?>',
        );

        $this->assertFalse($this->passesValidation($file));
    }

    public function test_file_with_forged_magic_bytes_is_rejected(): void
    {
        // PNG magic bytes followed by PHP code: the MIME sniffer sees an
        // image, but the file is not a structurally valid PNG.
        $file = UploadedFile::fake()->createWithContent(
            'polyglot.png',
            "\x89PNG\r\n\x1a\n<?php phpinfo(); ?>",
        );

        $this->assertFalse($this->passesValidation($file));
    }

    public function test_svg_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'vector.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>',
        );

        $this->assertFalse($this->passesValidation($file));
    }

    public function test_file_exceeding_size_limit_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('huge.jpg', ValidArtworkImage::MAX_SIZE_KB + 1);

        $this->assertFalse($this->passesValidation($file));
    }

    public function test_extension_that_does_not_match_actual_format_is_rejected(): void
    {
        $pngBytes = UploadedFile::fake()->image('real.png', 20, 20)->getContent();
        $file = UploadedFile::fake()->createWithContent('mismatch.jpg', $pngBytes);

        $this->assertFalse($this->passesValidation($file));
    }

    public function test_upload_form_rejects_disguised_file_and_does_not_create_artwork(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'shell.jpg',
            '<?php system($_GET["cmd"]); ?>',
        );

        // Act
        $this->actingAs($admin);
        Livewire::test(CreateArtwork::class)
            ->fillForm([
                'title' => 'Trojan Horse',
                'description' => 'Not actually an image.',
                'category_id' => $category->id,
                'file' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['file']);

        // Assert
        $this->assertDatabaseCount('artworks', 0);
    }
}
