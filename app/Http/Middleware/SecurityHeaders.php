<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        /** @var SymfonyResponse $response */
        $response = $next($request);

        // Content Security Policy
        $csp = $this->buildCsp($request);
        $response->headers->set('Content-Security-Policy', $csp);

        // HTTP Strict Transport Security (HSTS) - only on HTTPS
        if ($request->isSecure() || $request->headers->get('X-Forwarded-Proto') === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', 'DENY');

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');

        // X-XSS-Protection (legacy but still useful for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Remove Server header
        $response->headers->remove('Server');

        // Cross-Origin policies
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }

    /**
     * Build CSP policy based on environment and request context.
     */
    private function buildCsp(Request $request): string
    {
        $isAdmin = str_starts_with($request->path(), 'admin');
        $isDev = app()->environment('local', 'development');

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
        ];

        // Script sources
        $scriptSrc = [
            "'self'",
        ];

        if ($isDev) {
            // Vite HMR in development
            $scriptSrc[] = "'unsafe-inline'"; // Inertia needs inline scripts
            $scriptSrc[] = "'unsafe-eval'"; // Vite HMR uses eval
            $scriptSrc[] = "http://localhost:*"; // Vite dev server
            $scriptSrc[] = "ws://localhost:*"; // Vite HMR websocket
        } else {
            // Production: allow inline scripts for Inertia (nonce-based would be better but requires more setup)
            $scriptSrc[] = "'unsafe-inline'"; // TODO: Replace with nonce-based approach
        }

        $directives[] = "script-src " . implode(' ', $scriptSrc);

        // Style sources
        $styleSrc = [
            "'self'",
            "'unsafe-inline'", // Tailwind/Filament need inline styles
            "fonts.googleapis.com",
        ];

        if ($isDev) {
            $styleSrc[] = "http://localhost:*";
        }

        $directives[] = "style-src " . implode(' ', $styleSrc);

        // Font sources
        $fontSrc = [
            "'self'",
            "data:",
            "fonts.gstatic.com",
        ];

        if ($isDev) {
            $fontSrc[] = "http://localhost:*";
        }

        $directives[] = "font-src " . implode(' ', $fontSrc);

        // Image sources
        $imgSrc = [
            "'self'",
            "data:",
            "blob:",
        ];

        if ($isDev) {
            $imgSrc[] = "http://localhost:*";
        }

        $directives[] = "img-src " . implode(' ', $imgSrc);

        // Connect sources (for fetch, websocket, etc.)
        $connectSrc = [
            "'self'",
        ];

        if ($isDev) {
            $connectSrc[] = "ws://localhost:*";
            $connectSrc[] = "wss://localhost:*";
            $connectSrc[] = "http://localhost:*";
        }

        $directives[] = "connect-src " . implode(' ', $connectSrc);

        // Media sources
        $directives[] = "media-src 'self' blob: data:";

        // Frame sources (Filament may need iframes for some components)
        if ($isAdmin) {
            $directives[] = "frame-src 'self'";
        } else {
            $directives[] = "frame-src 'none'";
        }

        // Worker sources
        $directives[] = "worker-src 'self' blob:";

        // Manifest
        $directives[] = "manifest-src 'self'";

        return implode('; ', $directives);
    }
}
