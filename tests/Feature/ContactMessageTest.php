<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendContactMessage;
use App\Mail\ContactMessage;
use App\Models\ContactMessage as ContactMessageRecord;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function submission(): array
    {
        return ['name' => 'Art Lover', 'email' => 'visitor@example.com', 'message' => 'Is this artwork available?', 'consent' => true];
    }

    public function test_guests_can_submit_to_all_enabled_admins_only(): void
    {
        // Arrange: cover both admin roles, opted-out admins and ordinary users.
        Bus::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        User::factory()->create(['role' => UserRole::Admin, 'notify_on_contact_message' => false]);
        User::factory()->create();

        // Act: submit without signing in.
        $response = $this->from('/')->post(route('contact.store'), $this->submission());

        // Assert: queue one independent delivery per enabled admin.
        $response->assertRedirect('/')->assertSessionHasNoErrors();
        Bus::assertDispatchedTimes(SendContactMessage::class, 2);
        foreach ([$admin, $superAdmin] as $recipient) {
            Bus::assertDispatched(SendContactMessage::class, fn ($job) => $job->recipientId === $recipient->id
                && $job->senderEmail === 'visitor@example.com' && $job->messageBody === 'Is this artwork available?');
        }
    }

    public function test_submissions_are_stored_for_the_dashboard(): void
    {
        // Arrange
        Bus::fake();
        User::factory()->create(['role' => UserRole::Admin]);

        // Act
        $this->from('/')->post(route('contact.store'), $this->submission());

        // Assert
        $stored = ContactMessageRecord::query()->sole();
        $this->assertSame('Art Lover', $stored->sender_name);
        $this->assertSame('visitor@example.com', $stored->sender_email);
        $this->assertSame('Is this artwork available?', $stored->message);
    }

    public function test_invalid_submissions_do_not_queue_emails(): void
    {
        // Arrange: intercept all deliveries.
        Bus::fake();

        // Act: send invalid or oversized content and no consent.
        $response = $this->postJson(route('contact.store'), [
            'name' => "Name\r\nInjected", 'email' => 'invalid', 'message' => str_repeat('a', 10001), 'consent' => false,
        ]);

        // Assert: report field errors without queueing anything.
        $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'message', 'consent']);
        Bus::assertNothingDispatched();
    }

    public function test_no_recipients_returns_an_error(): void
    {
        // Arrange: no admins exist.
        Bus::fake();

        // Act: submit an otherwise valid message.
        $response = $this->postJson(route('contact.store'), $this->submission());

        // Assert: do not silently discard the message.
        $response->assertUnprocessable()->assertJsonValidationErrors('message');
        Bus::assertNothingDispatched();
    }

    public function test_contact_submissions_are_rate_limited(): void
    {
        // Arrange: accept messages without delivering them.
        Bus::fake();
        User::factory()->create(['role' => UserRole::Admin]);

        // Act: exhaust the limit for the caller's IP.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('contact.store'), $this->submission())->assertRedirect();
        }

        // Assert: the next submission is rejected.
        $this->postJson(route('contact.store'), $this->submission())->assertTooManyRequests();
        Bus::assertDispatchedTimes(SendContactMessage::class, 5);
    }

    public function test_delivery_uses_visitor_reply_to_and_escapes_content(): void
    {
        // Arrange: create an enabled admin and malicious-looking visitor text.
        Mail::fake();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $job = new SendContactMessage($admin->id, 'Visitor', 'visitor@example.com', '<script>alert(1)</script>');

        // Act: execute a queued delivery.
        $job->handle();

        // Assert: deliver privately, with a reply address and safe HTML.
        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail) use ($admin): bool {
            $this->assertSame('visitor@example.com', $mail->envelope()->replyTo[0]->address);
            $mail->assertSeeInHtml('&lt;script&gt;alert(1)&lt;/script&gt;', false);
            $mail->assertDontSeeInHtml('<script>', false);

            return $mail->hasTo($admin->email) && count($mail->to) === 1;
        });
        Mail::assertSentCount(1);
    }

    public function test_delivery_rechecks_role_preference_and_deleted_recipients(): void
    {
        // Arrange: queue recipients who will no longer qualify by delivery time.
        Mail::fake();
        $optedOut = User::factory()->create(['role' => UserRole::Admin, 'notify_on_contact_message' => false]);
        $demoted = User::factory()->create(['role' => UserRole::User]);
        $deleted = User::factory()->create(['role' => UserRole::Admin]);
        $deleted->delete();

        // Act: process each delivery.
        foreach ([$optedOut, $demoted, $deleted] as $recipient) {
            (new SendContactMessage($recipient->id, 'Visitor', 'visitor@example.com', 'Hello'))->handle();
        }

        // Assert: none of these recipients receive the message.
        Mail::assertNothingSent();
    }

    public function test_resend_transport_sends_the_expected_api_request(): void
    {
        // Arrange: exercise the real SDK and Laravel transport with a fake HTTP response.
        config([
            'services.resend.key' => 're_test_key',
            'mail.from.address' => 'gallery@example.com',
        ]);
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"test-email-id"}'),
        ]));
        $handler->push(Middleware::history($history));
        $client = new \Resend\Client(new HttpTransporter(
            new Client(['handler' => $handler]),
            BaseUri::from('api.resend.com'),
            Headers::withAuthorization(ApiKey::from('re_test_key')),
        ));
        $mailer = Mail::mailer('resend');
        $mailer->setSymfonyTransport(new ResendTransport($client));

        // Act: render and deliver through the SDK without contacting Resend.
        $mailer->to('admin@example.com')->send(new ContactMessage('Visitor', 'visitor@example.com', 'Hello gallery'));

        // Assert: the authenticated request keeps the site sender and visitor Reply-To.
        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame('https://api.resend.com/emails', (string) $request->getUri());
        $this->assertSame('Bearer re_test_key', $request->getHeaderLine('Authorization'));
        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame(['admin@example.com'], $payload['to']);
        $replyTo = Address::create($payload['reply_to'][0]);
        $this->assertSame('visitor@example.com', $replyTo->getAddress());
        $this->assertSame('Visitor', $replyTo->getName());
        $this->assertStringContainsString(config('mail.from.address'), $payload['from']);
        $this->assertStringContainsString('Hello gallery', $payload['html']);
    }

    public function test_admins_can_toggle_their_own_contact_email_preference(): void
    {
        // Arrange: both administrative roles should be able to manage the setting.
        foreach ([UserRole::Admin, UserRole::SuperAdmin] as $role) {
            $admin = User::factory()->create(['role' => $role]);
            $otherAdmin = User::factory()->create(['role' => $role]);

            // Act: disable and then re-enable the preference.
            foreach ([false, true] as $enabled) {
                $response = $this->actingAs($admin)->patch(route('profile.update'), [
                    'display_name' => $admin->display_name,
                    'email' => $admin->email,
                    'notify_on_contact_message' => $enabled,
                    'locale' => 'uk',
                    'notify_on_new_artwork' => true,
                ]);

                // Assert: only the current admin's setting changes.
                $response->assertSessionHasNoErrors();
                $this->assertSame($enabled, $admin->fresh()->notify_on_contact_message);
                $this->assertSame('uk', $admin->fresh()->locale);
                $this->assertTrue($admin->fresh()->notify_on_new_artwork);
                $this->assertTrue($otherAdmin->fresh()->notify_on_contact_message);
            }
        }
    }

    public function test_regular_users_cannot_change_the_contact_email_preference(): void
    {
        // Arrange: sign in as an ordinary user.
        $user = User::factory()->create();

        // Act: forge the admin-only profile field.
        $response = $this->actingAs($user)->patchJson(route('profile.update'), [
            'display_name' => $user->display_name, 'email' => $user->email, 'notify_on_contact_message' => false,
        ]);

        // Assert: reject the field without changing the preference.
        $response->assertUnprocessable()->assertJsonValidationErrors('notify_on_contact_message');
        $this->assertTrue($user->fresh()->notify_on_contact_message);
    }
}
