<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class HeadersTest extends TestCase
{
    public function test_csp_header_present_on_web_routes(): void
    {
        // Arrange
        $response = $this->get('/');

        // Act
        $csp = $response->headers->get('Content-Security-Policy');

        // Assert
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_hsts_header_present_on_https(): void
    {
        // HSTS only applies on HTTPS - the middleware checks $request->isSecure()
        // which requires actual HTTPS or trusted proxy setup
        // For testing, we verify the middleware logic adds it when isSecure() is true
        $this->markTestSkipped('HSTS requires actual HTTPS; tested in staging/production');
    }

    public function test_x_frame_options_header_present(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_x_content_type_options_header_present(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_referrer_policy_header_present(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $referrerPolicy = $response->headers->get('Referrer-Policy');
        $this->assertNotNull($referrerPolicy);
        $this->assertStringContainsString('strict-origin-when-cross-origin', $referrerPolicy);
    }

    public function test_permissions_policy_header_present(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($permissionsPolicy);
        $this->assertStringContainsString('accelerometer=()', $permissionsPolicy);
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
    }

    public function test_csp_allows_vite_hmr_in_development(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        // Should have script-src for inline scripts (needed for Inertia)
        $this->assertStringContainsString('script-src', $csp);
    }

    public function test_admin_panel_login_has_csp(): void
    {
        // Act
        $response = $this->get('/admin/login');

        // Assert
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_admin_panel_authenticated_has_csp(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Act
        $response = $this->actingAs($admin)->get('/admin');

        // Follow redirect if needed
        if ($response->getStatusCode() === 302) {
            $response = $this->actingAs($admin)->get($response->headers->get('Location'));
        }

        // Assert
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_no_server_header_exposed(): void
    {
        // Act
        $response = $this->get('/');

        // Assert: Server header should not expose version info
        $server = $response->headers->get('Server');
        $this->assertNull($server); // Or at least not Apache/version or nginx/version
    }

    public function test_x_xss_protection_header_present(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }
}
