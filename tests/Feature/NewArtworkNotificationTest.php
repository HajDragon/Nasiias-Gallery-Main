<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Artworks\Pages\CreateArtwork;
use App\Jobs\NotifySubscribersOfNewArtwork;
use App\Mail\NewArtworkPublished;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NewArtworkNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_update_the_notification_preference(): void
    {
        // Arrange
        // Nothing to set up: no user is authenticated, so the request is made as a guest.

        // Act
        $response = $this->putJson(route('notifications.new-artwork.update'), [
            'subscribed' => true,
        ]);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_user_can_opt_in_to_new_artwork_notifications(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->putJson(route('notifications.new-artwork.update'), [
            'subscribed' => true,
        ]);

        // Assert
        $response
            ->assertOk()
            ->assertJson(['subscribed' => true]);

        $this->assertTrue($user->fresh()->notify_on_new_artwork);
    }

    public function test_user_can_opt_out_of_new_artwork_notifications(): void
    {
        // Arrange
        $user = User::factory()->create(['notify_on_new_artwork' => true]);

        // Act
        $response = $this->actingAs($user)->putJson(route('notifications.new-artwork.update'), [
            'subscribed' => false,
        ]);

        // Assert
        $response
            ->assertOk()
            ->assertJson(['subscribed' => false]);

        $this->assertFalse($user->fresh()->notify_on_new_artwork);
    }

    public function test_subscribed_must_be_a_boolean(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $invalidValueResponse = $this->actingAs($user)
            ->putJson(route('notifications.new-artwork.update'), ['subscribed' => 'maybe']);

        $missingValueResponse = $this->actingAs($user)
            ->putJson(route('notifications.new-artwork.update'), []);

        // Assert
        $invalidValueResponse
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subscribed');

        $missingValueResponse
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subscribed');

        $this->assertNull($user->fresh()->notify_on_new_artwork);
    }

    public function test_new_artwork_email_is_sent_only_to_subscribed_users(): void
    {
        // Arrange
        Mail::fake();

        $subscribed = User::factory()->create(['notify_on_new_artwork' => true]);
        $optedOut = User::factory()->create(['notify_on_new_artwork' => false]);
        $undecided = User::factory()->create();
        $artwork = Artwork::factory()->create(['title' => 'Crimson Tide']);

        // Act
        (new NotifySubscribersOfNewArtwork($artwork))->handle();

        // Assert
        Mail::assertSent(
            NewArtworkPublished::class,
            fn (NewArtworkPublished $mail): bool => $mail->hasTo($subscribed->email)
                && str_contains($mail->envelope()->subject, 'Crimson Tide')
        );
        Mail::assertNotSent(
            NewArtworkPublished::class,
            fn (NewArtworkPublished $mail): bool => $mail->hasTo($optedOut->email)
        );
        Mail::assertNotSent(
            NewArtworkPublished::class,
            fn (NewArtworkPublished $mail): bool => $mail->hasTo($undecided->email)
        );
    }

    public function test_undecided_users_stay_undecided_until_they_choose(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        // No action is taken: the opt-in prompt has never been shown to this user.

        // Assert
        $this->assertNull($user->fresh()->notify_on_new_artwork);
    }

    public function test_creating_an_artwork_dispatches_the_subscriber_notification_job(): void
    {
        // Arrange
        Storage::fake('public');
        Bus::fake([NotifySubscribersOfNewArtwork::class]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        $file = UploadedFile::fake()->image('first-light.jpg', 1200, 800);

        // Act
        $this->actingAs($admin);
        $component = Livewire::test(CreateArtwork::class)
            ->fillForm([
                'title' => 'First Light',
                'description' => 'Dawn breaking over quiet water.',
                'category_id' => $category->id,
                'file' => $file,
            ])
            ->call('create');

        // Assert
        $component->assertHasNoFormErrors();

        $artwork = Artwork::where('title', 'First Light')->first();
        $this->assertNotNull($artwork);

        Bus::assertDispatched(
            NotifySubscribersOfNewArtwork::class,
            fn (NotifySubscribersOfNewArtwork $job): bool => $job->artwork->is($artwork)
        );
    }
}
