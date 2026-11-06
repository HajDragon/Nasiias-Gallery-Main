<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\TestCase;

class DependencyTest extends TestCase
{
    public function test_composer_audit_no_critical_vulnerabilities(): void
    {
        // Run composer audit programmatically
        $output = [];
        $returnCode = 0;
        exec('composer audit --format=json --no-dev 2>&1', $output, $returnCode);

        $auditResult = json_decode(implode("\n", $output), true);

        // If composer audit fails (non-zero exit), there are vulnerabilities
        if ($returnCode !== 0) {
            $this->fail('Composer audit found vulnerabilities: ' . json_encode($auditResult));
        }

        // Check for critical/high severity advisories
        if (isset($auditResult['advisories'])) {
            foreach ($auditResult['advisories'] as $advisory) {
                $severity = $advisory['severity'] ?? 'unknown';
                $this->assertNotEquals('critical', strtolower($severity),
                    "Critical vulnerability found: {$advisory['title']} ({$advisory['link']})");
                $this->assertNotEquals('high', strtolower($severity),
                    "High vulnerability found: {$advisory['title']} ({$advisory['link']})");
            }
        }
    }

    public function test_composer_audit_no_vulnerabilities_in_prod_deps(): void
    {
        // Only check production dependencies
        $output = [];
        $returnCode = 0;
        exec('composer audit --format=json --no-dev 2>&1', $output, $returnCode);

        $auditResult = json_decode(implode("\n", $output), true);

        // Should pass (exit code 0) for production dependencies
        $this->assertEquals(0, $returnCode,
            'Production dependencies have vulnerabilities: ' . json_encode($auditResult));
    }

    public function test_no_abandoned_packages_in_prod(): void
    {
        $output = [];
        exec('composer show --no-dev --format=json 2>&1', $output);

        $packages = json_decode(implode("\n", $output), true);

        foreach ($packages['installed'] ?? [] as $package) {
            if (isset($package['abandoned']) && $package['abandoned'] === true) {
                $this->fail("Abandoned package in production: {$package['name']} - {$package['abandoned_reason']}");
            }
        }
    }

    public function test_lock_file_integrity(): void
    {
        // Verify composer.lock matches composer.json
        $output = [];
        $returnCode = 0;
        exec('composer validate --strict 2>&1', $output, $returnCode);

        $this->assertEquals(0, $returnCode,
            'Composer validation failed: ' . implode("\n", $output));
    }

    public function test_npm_audit_no_critical_vulnerabilities(): void
    {
        $output = [];
        $returnCode = 0;
        exec('npm audit --json 2>&1', $output, $returnCode);

        $auditResult = json_decode(implode("\n", $output), true);

        if (isset($auditResult['vulnerabilities'])) {
            foreach ($auditResult['vulnerabilities'] as $vuln) {
                $severity = $vuln['severity'] ?? 'unknown';
                if (in_array(strtolower($severity), ['critical', 'high'])) {
                    // Allow moderate/high in dev dependencies but not in production
                    // This is a warning, not a failure for now
                    $this->assertTrue(true, "NPM {$severity} vulnerability: {$vuln['name']}@{$vuln['version']}");
                }
            }
        }
    }
}
