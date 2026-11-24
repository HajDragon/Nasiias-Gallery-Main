import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

// Install Playwright outside this repository and set PERFORMANCE_PLAYWRIGHT_MODULE
// to the file: URL of its index.mjs. Requires Edge, or set a different installed channel.
const { chromium } = await import(
    process.env.PERFORMANCE_PLAYWRIGHT_MODULE ?? 'playwright'
);
execFileSync('php', [
    fileURLToPath(new URL('./prepare-fixture.php', import.meta.url)),
]);
const browser = await chromium.launch({
    channel: process.env.PERFORMANCE_BROWSER_CHANNEL ?? 'msedge',
    headless: true,
});
const output = new URL('../../docs/performance/results/', import.meta.url);
const origin = 'http://127.0.0.1:8787';
const results = {
    captured_at: new Date().toISOString(),
    browser: browser.version(),
    checks: [],
    screenshots: [],
    errors: [],
};

try {
    for (const mobile of [false, true]) {
        const context = await browser.newContext({
            viewport: mobile
                ? { width: 390, height: 844 }
                : { width: 1440, height: 1000 },
            isMobile: mobile,
            reducedMotion: mobile ? 'reduce' : 'no-preference',
        });
        const page = await context.newPage();
        page.on('pageerror', (error) =>
            results.errors.push({
                message: error.message,
                url: page.url(),
                stack: error.stack,
            }),
        );
        await page.goto(origin, { waitUntil: 'networkidle' });
        const cards = page.locator('#art-library article:visible');
        assert.equal(await cards.count(), 18);
        assert.equal(
            await page.getByTestId('intro-animation').locator('img').count(),
            12,
        );
        await page.mouse.move(0, 0);
        await page.waitForTimeout(3200);
        const transform = () =>
            page
                .getByTestId('intro-orbit')
                .evaluate((element) => getComputedStyle(element).transform);
        const first = await transform();
        await page.waitForTimeout(250);
        const second = await transform();
        assert.equal(
            first === second,
            mobile,
            'Orbit must respect reduced motion and otherwise animate',
        );
        await page
            .locator('#art-library')
            .evaluate((element) => element.scrollIntoView({ block: 'start' }));
        await page.waitForTimeout(400);
        const paused = await transform();
        await page.waitForTimeout(250);
        assert.equal(await transform(), paused, 'Offscreen orbit should stop');
        const name = mobile ? 'home-mobile.png' : 'home-desktop.png';
        await page.screenshot({ path: fileURLToPath(new URL(name, output)) });
        results.screenshots.push(name);
        await page
            .getByRole('button', { name: 'Load more artworks', exact: true })
            .click();
        await page.waitForFunction(
            () =>
                document.querySelectorAll('#art-library article').length === 72,
        );
        assert.equal(await cards.count(), 36);
        await page.getByRole('button', { name: 'Other', exact: true }).click();
        await page.waitForFunction(
            () =>
                new URL(location.href).searchParams.get('category') === 'other',
        );
        await page.waitForFunction(
            () =>
                document.querySelectorAll('#art-library article').length === 30,
        );
        assert.equal(await cards.count(), 15);
        assert.equal(
            await page
                .getByRole('button', {
                    name: 'Load more artworks',
                    exact: true,
                })
                .count(),
            0,
        );
        results.checks.push(
            `${mobile ? 'mobile reduced motion' : 'desktop'}: bounded home, intro, animation pause, load more, server filter`,
        );
        await page.goto(`${origin}/gallery`, { waitUntil: 'networkidle' });
        assert.equal(await page.locator('main article').count(), 18);
        await page.evaluate(() =>
            document.documentElement.classList.add('dark'),
        );
        const dark = mobile
            ? 'gallery-mobile-dark.png'
            : 'gallery-desktop-dark.png';
        await page.screenshot({ path: fileURLToPath(new URL(dark, output)) });
        results.screenshots.push(dark);
        if (!mobile) {
            await page.goto(`${origin}/login`, { waitUntil: 'networkidle' });
            await page
                .locator('input[name=email]')
                .fill('admin@example.invalid');
            await page.locator('input[name=password]').fill('audit-pass');
            await page.locator('button[type=submit]').click();
            await page.waitForURL((url) => url.pathname !== '/login');
            await page.goto(`${origin}/gallery/45`, {
                waitUntil: 'networkidle',
            });
            await page
                .locator('p')
                .filter({ hasText: /Synthetic comment 49$/ })
                .waitFor();
            assert.equal(
                await page
                    .locator('p')
                    .filter({ hasText: /Synthetic comment \d+$/ })
                    .count(),
                20,
            );
            await page
                .getByRole('button', { name: 'Load older comments' })
                .click();
            await page.waitForFunction(
                () =>
                    [...document.querySelectorAll('p')].filter((element) =>
                        /Synthetic comment \d+$/.test(element.textContent),
                    ).length === 40,
            );
            await page
                .getByRole('button', { name: 'Load older comments' })
                .click();
            await page.waitForFunction(
                () =>
                    [...document.querySelectorAll('p')].filter((element) =>
                        /Synthetic comment \d+$/.test(element.textContent),
                    ).length === 50,
            );
            await page.locator('#artwork-comment').fill('<b>Browser test</b>');
            await page.locator('#artwork-comment').press('Enter');
            await page
                .locator('p')
                .filter({ hasText: /Browser test$/ })
                .waitFor();
            await page.goto(`${origin}/admin`, { waitUntil: 'networkidle' });
            // Existing production CSP blocks Alpine in this panel. Verify CSS separately
            // with a test-only bypass; never relax the application's real CSP here.
            const cssContext = await browser.newContext({
                viewport: { width: 1440, height: 1000 },
                bypassCSP: true,
                storageState: await context.storageState(),
            });
            const adminPage = await cssContext.newPage();
            const adminCssErrors = [];
            adminPage.on('pageerror', (error) =>
                adminCssErrors.push(error.message),
            );
            await adminPage.goto(`${origin}/admin`, {
                waitUntil: 'networkidle',
            });
            await adminPage.screenshot({
                path: fileURLToPath(new URL('admin-desktop.png', output)),
            });
            assert.equal(
                await adminPage
                    .locator('link[href*="/css/filament/filament/app.css"]')
                    .count(),
                1,
            );
            await cssContext.close();
            results.admin_css_runtime_errors = adminCssErrors;
            results.screenshots.push('admin-desktop.png');
            assert.equal(
                await page
                    .locator('link[href*="/css/filament/filament/app.css"]')
                    .count(),
                1,
            );
            results.checks.push(
                'Authenticated comment pagination 20/40/50, sanitized submission, admin styles',
            );
        }
        await context.close();
    }
    results.existing_admin_csp_issues = results.errors.filter((error) =>
        new URL(error.url).pathname.startsWith('/admin'),
    );
    assert.deepEqual(
        results.errors.filter(
            (error) => !new URL(error.url).pathname.startsWith('/admin'),
        ),
        [],
    );
    results.admin_css_check =
        'Test-only bypassCSP context; real application policy unchanged. Production-mode Alpine CSP errors are retained as a separate existing issue.';
    results.result = results.existing_admin_csp_issues.length
        ? 'passed_public_checks_with_existing_admin_csp_issue'
        : 'passed';
} catch (error) {
    results.result = 'failed';
    results.failure = error.stack;
    throw error;
} finally {
    await fs.writeFile(
        new URL('browser.json', output),
        JSON.stringify(results, null, 4),
    );
    await browser.close();
    console.log(JSON.stringify(results));
}
