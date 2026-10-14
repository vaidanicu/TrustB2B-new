import { test, expect } from '@playwright/test';

const headlines = {
    '/': 'Fotografii de eveniment. Consimțământ clar. Control deplin.',
    '/en': 'Event photography. Clear consent. Complete control.',
    '/de': 'Eventfotos. Klare Einwilligung. Volle Kontrolle.',
};

for (const width of [320, 390, 430, 768, 1024, 1366, 1440]) {
    test(`homepage navigation and layout at ${width}px`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setViewportSize({ width, height: 950 });
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(headlines['/']);
        await expect(page.getByText('Date demonstrative', { exact: true }).first()).toBeVisible();
        await page.getByRole('link', { name: 'Descoperă platforma', exact: true }).click();
        await expect(page).toHaveURL(/#platforma$/);
        await expect(page.locator('#platforma')).toBeInViewport();
        await page.getByRole('link', { name: 'Vezi cum funcționează', exact: true }).click();
        await expect(page).toHaveURL(/#cum-functioneaza$/);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
        expect(overflow).toBe(false);
        if (width < 1024) {
            await page.getByRole('button', { name: 'Meniu', exact: true }).click();
            await expect(page.getByRole('button', { name: 'Meniu', exact: true })).toHaveAttribute('aria-expanded', 'true');
            await page.keyboard.press('Escape');
            await expect(page.getByRole('button', { name: 'Meniu', exact: true })).toHaveAttribute('aria-expanded', 'false');
        }
        await page.getByRole('link', { name: 'Autentificare', exact: true }).first().click();
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByLabel('Email', { exact: true })).toBeVisible();
        expect(errors).toEqual([]);
    });
}

for (const [path, headline] of Object.entries(headlines)) {
    test(`localized content and complete layout: ${path}`, async ({ page }) => {
        await page.setViewportSize({ width: 320, height: 850 });
        await page.goto(path);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(headline);
        await expect(page.locator('main')).toHaveAttribute('lang', path === '/' ? 'ro' : path.slice(1));
        await expect(page.locator('html')).toHaveAttribute('lang', path === '/' ? 'ro' : path.slice(1));
        expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
        await page.locator('header').getByRole('link', { name: path === '/en' ? 'DE' : 'EN', exact: true }).click();
        await expect(page).toHaveURL(path === '/en' ? /\/de$/ : /\/en$/);
    });
}

test('preview invitation links to the existing demonstration route', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('link', { name: 'Vezi invitația demo', exact: true }).click();
    await expect(page).toHaveURL(/\/invitatie$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Turneu');
});

test('existing login reports required field errors', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: 'Autentificare', exact: true }).click();
    await expect(page.getByText('The email field is required.', { exact: true })).toBeVisible();
    await expect(page.getByText('The password field is required.', { exact: true })).toBeVisible();
});

test('reduced motion keeps content visible', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.locator('#platforma')).toBeVisible();
});


for (const width of [320, 1440]) {
    test(`interactive preview and visual capture at ${width}px`, async ({ page }, testInfo) => {
        const errors = [];
        page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
        await page.setViewportSize({ width, height: 1000 });
        await page.goto('/');
        await expect(page.locator('.tb-event-cover img')).toBeVisible();
        expect(await page.locator('.tb-event-cover img').evaluate(image => image.complete && image.naturalWidth > 0)).toBe(true);
        await page.screenshot({ path: testInfo.outputPath(`homepage-${width}.png`), fullPage: true });
        await page.getByRole('button', { name: 'Participanți', exact: true }).click();
        await expect(page.getByText('Maria Weber', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Participanți', exact: true })).toHaveAttribute('aria-pressed', 'true');
        await page.getByRole('button', { name: 'Jurnal de audit', exact: true }).click();
        await expect(page.getByText('Exemple de operațiuni; nu sunt înregistrări reale.', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Evenimente', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Business Forum · București', exact: true })).toBeVisible();
        expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
        expect(errors).toEqual([]);
    });
}
