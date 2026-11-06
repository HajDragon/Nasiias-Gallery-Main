<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\TestCase;

class HeadersTest extends TestCase
{
    public function test_csp_header_present_on_web_routes(): void
    {
        $response = $this->get('/');

        $this->assertHeaderContains($response, 'Content-Security-Policy', "default-src 'self'");
    }

    public function test_hsts_header_present_on_https(): void
    {
        // HSTS only applies on HTTPS, but we can check the middleware adds it
        $response = $this->get('/', [], [], [], ['HTTPS' => 'on']);

        $this->assertHeaderContains($response, 'Strict-Transport-Security', 'max-age=');
    }

    public function test_x_frame_options_header_present(): void
    {
        $response = $this->get('/');

        $this->assertHeaderEquals($response, 'X-Frame-Options', 'DENY');
    }

    public function test_x_content_type_options_header_present(): void
    {
        $response = $this->get('/');

        $this->assertHeaderEquals($response, 'X-Content-Type-Options', 'nosniff');
    }

    public function test_referrer_policy_header_present(): void
    {
        $response = $this->get('/');

        $this->assertHeaderContains($response, 'Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_header_present(): void
    {
        $response = $this->get('/');

        $this->assertHeaderContains($response, 'Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
    }

    public function test_csp_allows_vite_hmr_in_development(): void
    {
        // In development, CSP should allow Vite HMR websocket connections
        // This test documents the expected behavior
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        // Should have script-src for inline scripts (needed for Inertia)
        $this->assertStringContainsString("script-src", $csp);
    }

    public function test_admin_panel_has_csp(): void
    {
        // Admin panel should also have CSP headers
        $admin = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/admin');

        $this->assertHeaderContains($response, 'Content-Security-Policy', "default-src 'self'");
    }

    public function test_no_server_header_exposed(): void
    {
        $response = $this->get('/');

        // Server header should not expose version info
        $server = $response->headers->get('Server');
        $this->assertNull($server); // Or at least not Apache/version or nginx/version
    }

    public function test_x_xss_protection_header_present(): void
    {
        $response = $this->get('/');

        $this->assertHeaderEquals($response, 'X-XSS-Protection', '1; mode=block');
    }
}
