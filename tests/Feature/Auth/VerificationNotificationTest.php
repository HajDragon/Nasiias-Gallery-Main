<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailAddress;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class VerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
    }

    public function test_sends_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_does_not_send_verification_notification_if_email_is_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home', absolute: false));

        Notification::assertNothingSent();
    }

    public function test_verification_notification_sends_the_branded_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function (VerifyEmail $notification) use ($user): bool {
                $mail = $notification->toMail($user);

                return $mail instanceof VerifyEmailAddress && $mail->hasTo($user->email);
            },
        );
    }

    public function test_verification_email_includes_the_gallery_branding(): void
    {
        $user = User::factory()->unverified()->create(['display_name' => 'Ava Stone']);

        $mail = (new VerifyEmail)->toMail($user);

        $this->assertInstanceOf(VerifyEmailAddress::class, $mail);
        $mail->assertHasSubject(__('emails.verify.subject', ['app' => config('app.name')]));
        $mail->assertSeeInHtml(asset('nastias-gallery-logo.png'), false);
        $mail->assertSeeInHtml(config('app.name'));
        $mail->assertSeeInHtml(asset('fonts/Magilio.ttf'), false);
        $mail->assertSeeInHtml(asset('fonts/Libron-Regular.woff2'), false);
        $mail->assertSeeInHtml('Hello Ava Stone,');
    }

    public function test_verification_email_uses_the_users_locale(): void
    {
        $user = User::factory()->unverified()->create(['locale' => 'uk']);

        $mail = (new VerifyEmail)->toMail($user);

        $mail->assertHasSubject(__('emails.verify.subject', [], 'uk'));
        $mail->assertSeeInHtml(__('emails.verify.action', [], 'uk'));
    }
}
