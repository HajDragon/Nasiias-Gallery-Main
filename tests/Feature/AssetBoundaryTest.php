<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_keeps_its_own_styles_after_public_styles_are_separated(): void
    {
        // Arrange: the application has distinct public and Filament entry points.
        // Act: render each document through the normal HTTP stack.
        $public = $this->get('/');
        $admin = $this->get('/admin/login');

        // Assert: Filament retains its registered stylesheet and custom font/theme layer.
        $public->assertOk()->assertDontSee('/css/filament/filament/app.css', false);
        $admin->assertOk()->assertSee('/css/filament/filament/app.css', false)->assertSee('/css/nastia.css', false);
    }
}
