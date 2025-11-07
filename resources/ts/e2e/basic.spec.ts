import { test, expect } from '@playwright/test';

test.describe('Basic Application Test', () => {
    test('should load the application', async ({ page }) => {
        // Aller à la page d'accueil (ou login si pas d'accueil publique)
        await page.goto('/');

        // Vérifier que la page charge (titre ou élément présent)
        await expect(page).toHaveTitle(/Examena/);
    });

    test('should have working CSS and JavaScript', async ({ page }) => {
        await page.goto('/');

        // Vérifier qu'aucune erreur JS critique n'est présente
        const jsErrors: string[] = [];
        page.on('pageerror', (error) => {
            jsErrors.push(error.message);
        });

        // Attendre que la page soit chargée
        await page.waitForLoadState('networkidle');

        // Ne pas avoir d'erreurs JS critiques
        expect(jsErrors.filter(error =>
            !error.includes('ResizeObserver') &&
            !error.includes('Non-Error promise rejection')
        )).toHaveLength(0);
    });
});