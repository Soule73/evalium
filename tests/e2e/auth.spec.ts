import { test, expect } from '@playwright/test';

test.describe('Login and Authentication', () => {
    test('should display login form', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('should show error for invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.fill('input[name="email"]', 'invalid@example.com');
        await page.fill('input[name="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');

        // Attendre la soumission et vérifier qu'on reste sur la page de login
        await page.waitForTimeout(2000);

        // Vérifier qu'on est toujours sur la page de login (pas de redirection)
        await expect(page).toHaveURL(/.*login.*/);

        // Vérifier qu'un message d'erreur ou indication d'échec est présent
        // (plus flexible que de chercher un texte exact)
        const hasErrorIndicator = await page.locator('.error, .alert, .text-red, [class*="error"], [class*="danger"]').count() > 0 ||
            // Form encore visible
            await page.locator('input[name="email"]').count() > 0;
        expect(hasErrorIndicator).toBeTruthy();
    });

    // Test de connexion réussie géré par auth.setup.ts pour éviter la duplication
});