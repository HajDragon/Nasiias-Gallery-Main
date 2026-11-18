<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_locale_can_be_switched()
    {
        $response = $this
            ->from(route('home'))
            ->post(route('locale.update', ['locale' => 'uk']));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertSame('uk', session('locale'));
    }

    public function test_unsupported_locale_is_rejected()
    {
        $this
            ->from(route('home'))
            ->post(route('locale.update', ['locale' => 'de']))
            ->assertNotFound();
    }

    public function test_authenticated_user_locale_is_persisted()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('home'))
            ->post(route('locale.update', ['locale' => 'uk']))
            ->assertRedirect(route('home'));

        $this->assertSame('uk', $user->refresh()->locale);
    }

    public function test_profile_update_persists_locale()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'display_name' => 'Test User',
                'email' => $user->email,
                'locale' => 'uk',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('uk', $user->refresh()->locale);
        $this->assertSame('uk', session('locale'));
    }

    public function test_ukrainian_translations_are_shared_with_inertia()
    {
        $this
            ->withSession(['locale' => 'uk'])
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'uk')
                ->where('translations.nav.home', 'Головна')
                ->where('fallbackTranslations.nav.home', 'Home')
            );
    }
}
