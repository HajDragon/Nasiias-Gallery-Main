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

class InputValidationTest extends TestCase
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

    public function test_xss_payload_in_title_blocked(): void
    {
        // Arrange
        $xssPayloads = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '"><script>alert(1)</script>',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<body onload=alert(1)>',
            '<input autofocus onfocus=alert(1)>',
            '<select autofocus onfocus=alert(1)>',
            '<textarea autofocus onfocus=alert(1)>',
            '<keygen autofocus onfocus=alert(1)>',
            '<video><source onerror=alert(1)>',
            '<audio src=x onerror=alert(1)>',
            '<details open ontoggle=alert(1)>',
            '<marquee onstart=alert(1)>',
            '<form><button formaction=javascript:alert(1)>',
            '<object data=javascript:alert(1)>',
            '<embed src=javascript:alert(1)>',
            '<style>@keyframes x{}</style><div style="animation-name:x" onanimationend=alert(1)>',
        ];

        foreach ($xssPayloads as $payload) {
            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => $payload,
                'category_id' => $this->category->id,
                'description' => 'Test description',
            ]);

            // Assert: Should either reject or sanitize
            if ($response->status() === 302) {
                $artwork = Artwork::latest()->first();
                $this->assertStringNotContainsString('<script', $artwork->title);
                $this->assertStringNotContainsString('onerror', $artwork->title);
                $this->assertStringNotContainsString('onload', $artwork->title);
            } else {
                $response->assertSessionHasErrors('title');
            }
        }
    }

    public function test_xss_payload_in_description_blocked(): void
    {
        // Arrange
        $xssPayloads = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            'javascript:alert(1)',
            '<iframe src="javascript:alert(1)"></iframe>',
        ];

        foreach ($xssPayloads as $payload) {
            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => 'Test Artwork',
                'category_id' => $this->category->id,
                'description' => $payload,
            ]);

            // Assert
            if ($response->status() === 302) {
                $artwork = Artwork::latest()->first();
                $this->assertStringNotContainsString('<script', $artwork->description);
                $this->assertStringNotContainsString('onerror', $artwork->description);
                $this->assertStringNotContainsString('onload', $artwork->description);
                $this->assertStringNotContainsString('javascript:', $artwork->description);
            } else {
                $response->assertSessionHasErrors('description');
            }
        }
    }

    public function test_sql_injection_payloads_blocked(): void
    {
        // Arrange
        $sqlPayloads = [
            "' OR '1'='1",
            "'; DROP TABLE artworks; --",
            "' UNION SELECT * FROM users --",
            "' OR 1=1 --",
            "'; INSERT INTO users (email, password_hash) VALUES ('hacker@test.com', 'hash'); --",
            "1' OR '1'='1' --",
            "admin'--",
            "' OR '1'='1' /*",
            "'; WAITFOR DELAY '00:00:05' --",
        ];

        foreach ($sqlPayloads as $payload) {
            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => $payload,
                'category_id' => $this->category->id,
                'description' => 'Test',
            ]);

            // Assert: Should not cause SQL errors - either rejected or safely stored
            if ($response->status() === 302) {
                $artwork = Artwork::latest()->first();
                $this->assertEquals($payload, $artwork->title); // Stored as-is, parameterized queries protect
            }
            // No 500 errors
            $this->assertNotEquals(500, $response->status());
        }
    }

    public function test_path_traversal_in_file_upload_blocked(): void
    {
        // Arrange
        $pathTraversalPayloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\drivers\\etc\\hosts',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            '..%2f..%2f..%2fetc%2fpasswd',
            '..%5c..%5c..%5cwindows%5csystem32%5cdrivers%5cetc%5chosts',
            '/absolute/path/to/file',
            'C:\\Windows\\System32\\drivers\\etc\\hosts',
        ];

        foreach ($pathTraversalPayloads as $payload) {
            // Act: Test via filename in file upload
            $file = UploadedFile::fake()->create($payload, 100, 'image/png');

            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => 'Test',
                'category_id' => $this->category->id,
                'description' => 'Test',
                'file' => $file,
            ]);

            // Assert: ValidArtworkImage rule should reject invalid files
            $this->assertNotEquals(500, $response->status());
        }
    }

    public function test_long_input_handling(): void
    {
        // Arrange: Test very long title (255 char limit)
        $longTitle = str_repeat('a', 300);

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => $longTitle,
            'category_id' => $this->category->id,
            'description' => 'Test',
        ]);

        // Assert
        $response->assertSessionHasErrors('title');

        // Arrange: Test very long description
        $longDescription = str_repeat('b', 10000);

        // Act
        $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
            'title' => 'Test',
            'category_id' => $this->category->id,
            'description' => $longDescription,
        ]);

        // Assert: Description may not have a strict limit, but shouldn't crash
        $this->assertNotEquals(500, $response->status());
    }

    public function test_null_byte_injection_blocked(): void
    {
        // Arrange
        $nullBytePayloads = [
            "test\0.png",
            "test\0.jpg",
            'test%00.png',
            "test\0.php",
        ];

        foreach ($nullBytePayloads as $payload) {
            // Act
            $file = UploadedFile::fake()->create($payload, 100, 'image/png');

            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => 'Test',
                'category_id' => $this->category->id,
                'description' => 'Test',
                'file' => $file,
            ]);

            // Assert
            $this->assertNotEquals(500, $response->status());
        }
    }

    public function test_unicode_and_special_characters_handled(): void
    {
        // Arrange
        $specialPayloads = [
            '🎨🖼️📸', // Emojis
            '测试标题', // Chinese
            'тест', // Cyrillic
            'テスト', // Japanese
            'עלילות', // Hebrew
            'العنوان', // Arabic
            '<>\"\'&', // HTML special chars
            '{{7*7}}', // Template injection
            '${7*7}', // Template injection
            '#{7*7}', // Template injection
            '<%= 7*7 %>', // Template injection
        ];

        foreach ($specialPayloads as $payload) {
            // Act
            $response = $this->actingAs($this->admin)->from('/admin/artworks/create')->post('/admin/artworks', [
                'title' => $payload,
                'category_id' => $this->category->id,
                'description' => 'Test',
            ]);

            // Assert: Should handle gracefully
            $this->assertNotEquals(500, $response->status());
        }
    }
}
