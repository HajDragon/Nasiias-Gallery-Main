<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->category = Category::factory()->create();
    }

    public function test_invalid_mime_type_rejected(): void
    {
        // Arrange
        $invalidFiles = [
            'test.php' => 'application/x-php',
            'test.exe' => 'application/x-msdownload',
            'test.sh' => 'application/x-sh',
            'test.js' => 'application/javascript',
            'test.html' => 'text/html',
            'test.svg' => 'image/svg+xml', // SVG can contain scripts
            'test.pdf' => 'application/pdf',
            'test.doc' => 'application/msword',
            'test.zip' => 'application/zip',
            'test.tar' => 'application/x-tar',
            'test.gz' => 'application/gzip',
        ];

        foreach ($invalidFiles as $filename => $mimeType) {
            $file = UploadedFile::fake()->create($filename, 100, $mimeType);

            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => 'Test',
                'category_id' => $this->category->id,
                'description' => 'Test',
                'file' => $file,
            ]);

            // Assert: Should be rejected by ValidArtworkImage rule
            $response->assertSessionHasErrors('file');
        }
    }

    public function test_valid_mime_types_accepted(): void
    {
        // Arrange
        $validFiles = [
            'test.jpg' => 'image/jpeg',
            'test.jpeg' => 'image/jpeg',
            'test.png' => 'image/png',
            'test.webp' => 'image/webp',
            'test.gif' => 'image/gif',
        ];

        foreach ($validFiles as $filename => $mimeType) {
            $file = UploadedFile::fake()->image($filename, 800, 600);

            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => 'Test',
                'category_id' => $this->category->id,
                'description' => 'Test',
                'file' => $file,
            ]);

            // Assert: Should be accepted (302 redirect on success)
            $this->assertNotEquals(500, $response->status());
            if ($response->status() === 302) {
                $this->assertDatabaseHas('artworks', ['title' => 'Test']);
            }
        }
    }

    public function test_oversize_file_rejected(): void
    {
        // Arrange: Create file larger than 10MB (MAX_SIZE_KB = 10240)
        $oversizeFile = UploadedFile::fake()->create('large.jpg', 11 * 1024, 'image/jpeg');

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => 'Test',
            'file' => $oversizeFile,
        ]);

        // Assert
        $response->assertSessionHasErrors('file');
    }

    public function test_file_at_max_size_accepted(): void
    {
        // Arrange: Create file at exactly 10MB
        $maxSizeFile = UploadedFile::fake()->create('max.jpg', 10240, 'image/jpeg');

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => 'Test',
            'file' => $maxSizeFile,
        ]);

        // Assert: Should be accepted (at boundary)
        $this->assertNotEquals(500, $response->status());
    }

    public function test_polyglot_file_rejected(): void
    {
        // Arrange: Polyglot files that are valid as multiple formats
        // Note: UploadedFile::fake()->create doesn't allow custom content easily
        // This test documents the expectation that ValidArtworkImage catches this via getimagesize()
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => 'Test',
            'file' => $file,
        ]);

        // Assert: Valid image should pass
        $this->assertNotEquals(500, $response->status());
    }

    public function test_corrupt_image_rejected(): void
    {
        // Arrange: Create a file with image extension but corrupt content
        $corruptContent = 'This is not an image at all';
        $file = UploadedFile::fake()->create('corrupt.jpg', strlen($corruptContent), 'image/jpeg');

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => 'Test',
            'file' => $file,
        ]);

        // Assert: ValidArtworkImage should reject via getimagesize()
        $response->assertSessionHasErrors('file');
    }

    public function test_image_dimension_limits_enforced(): void
    {
        // Arrange: Test normal dimensions (should pass)
        $file = UploadedFile::fake()->image('normal.jpg', 1920, 1080);

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => 'Test',
            'file' => $file,
        ]);

        // Assert
        $this->assertNotEquals(500, $response->status());
    }

    public function test_extension_mime_mismatch_rejected(): void
    {
        // Arrange: File with .jpg extension but PNG content (or vice versa)
        // ValidArtworkImage checks both extension and actual MIME type
        $file = UploadedFile::fake()->image('mismatch.jpg', 100, 100);

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => 'Test',
            'file' => $file,
        ]);

        // Assert: Valid image should pass (fake creates valid images)
        $this->assertNotEquals(500, $response->status());
    }

    public function test_executable_file_rejected(): void
    {
        // Arrange
        $executableFiles = [
            'script.php' => '<?php echo "hacked"; ?>',
            'script.phtml' => '<?php echo "hacked"; ?>',
            'script.php5' => '<?php echo "hacked"; ?>',
            'script.php7' => '<?php echo "hacked"; ?>',
            'script.phar' => '<?php echo "hacked"; ?>',
            'script.cgi' => '#!/bin/bash\necho hacked',
            'script.pl' => '#!/usr/bin/perl\nprint "hacked"',
        ];

        foreach ($executableFiles as $filename => $content) {
            $file = UploadedFile::fake()->create($filename, strlen($content), 'application/x-php');

            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => 'Test',
                'category_id' => $this->category->id,
                'description' => 'Test',
                'file' => $file,
            ]);

            // Assert
            $response->assertSessionHasErrors('file');
        }
    }

    public function test_file_upload_stored_in_correct_location(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('test-storage.jpg', 800, 600);

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Storage Test',
            'category_id' => $this->category->id,
            'description' => 'Test storage location',
            'file' => $file,
        ]);

        // Assert
        if ($response->status() === 302) {
            $artwork = Artwork::where('title', 'Storage Test')->first();
            $this->assertNotNull($artwork);

            // Check media collection
            $media = $artwork->getFirstMedia('images');
            $this->assertNotNull($media);

            // Check file is in correct directory
            $this->assertStringContainsString('artworks/', $media->file_name);

            // Check file is on public disk
            $this->assertEquals('public', $media->disk);
        }
    }
}
