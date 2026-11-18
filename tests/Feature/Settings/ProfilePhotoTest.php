<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_a_profile_photo(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
            ]);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();

        $this->assertCount(1, $user->getMedia('avatar'));
        $this->assertNotNull($user->avatar);
    }

    public function test_uploading_a_new_photo_replaces_the_previous_one(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('first.jpg', 400, 400))
            ->toMediaCollection('avatar');

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('second.jpg', 400, 400),
            ]);

        // Assert
        $response->assertSessionHasNoErrors();

        $this->assertCount(1, $user->fresh()->getMedia('avatar'));
    }

    public function test_user_cannot_upload_an_invalid_profile_photo(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->create(
                    'avatar.php',
                    10,
                    'application/x-php',
                ),
            ]);

        // Assert
        $response->assertSessionHasErrors('avatar');

        $this->assertCount(0, $user->fresh()->getMedia('avatar'));
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('avatar.jpg', 400, 400))
            ->toMediaCollection('avatar');

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('profile.avatar.destroy'));

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();

        $this->assertCount(0, $user->getMedia('avatar'));
        $this->assertNull($user->avatar);
    }
}
