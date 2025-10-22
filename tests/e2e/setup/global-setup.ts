import { chromium, FullConfig } from '@playwright/test';

async function globalSetup(config: FullConfig) {
    console.log('Configuration globale des tests Playwright...');

    const { baseURL } = config.projects[0].use;

    // Créer une instance de navigateur pour le setup
    const browser = await chromium.launch();
    const page = await browser.newPage();

    try {
        console.log(`Vérification de la disponibilité du serveur: ${baseURL}`);
        await page.goto(baseURL || 'http://localhost:8000');
        console.log('Serveur disponible');
    } catch (error) {
        console.error('Erreur lors du setup global:', error);
        throw error;
    } finally {
        await browser.close();
    }

    console.log('Configuration globale terminée');
}

export default globalSetup;