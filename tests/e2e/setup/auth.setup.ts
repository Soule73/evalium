import { test as setup, expect } from '@playwright/test';

const authFile = 'playwright/.auth/user.json';

setup('authenticate', async ({ page }) => {
    console.log("Configuration de l'authentification...");

    // Aller à la page de connexion
    await page.goto('/login');

    // Attendre que la page soit chargée
    await expect(page.locator('h1')).toContainText('Connexion');

    // Remplir les champs de connexion
    await page.fill('input[name="email"]', 'admin@test.com');
    await page.fill('input[name="password"]', 'password123');

    // Cliquer sur le bouton de connexion
    await page.click('button[type="submit"]');

    // Attendre la redirection après connexion (admin va vers /admin/dashboard/admin)
    await page.waitForURL('**/admin/dashboard/admin');

    // Sauvegarder l'état d'authentification
    await page.context().storageState({ path: authFile });

    console.log('Authentification configurée et sauvegardée');
});

// Test alternatif pour l'authentification d'un étudiant
setup('authenticate student', async ({ page }) => {
    console.log("Configuration de l'authentification étudiant...");

    const studentAuthFile = 'playwright/.auth/student.json';

    await page.goto('/login');
    await expect(page.locator('h1')).toContainText('Connexion');

    await page.fill('input[name="email"]', 'alice.bernard@test.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.waitForURL('**/dashboard/student');

    await page.context().storageState({ path: studentAuthFile });

    console.log("Authentification étudiant configurée");
});

// Test alternatif pour l'authentification d'un enseignant
setup('authenticate teacher', async ({ page }) => {
    console.log('Configuration de l\'authentification enseignant...');

    const teacherAuthFile = 'playwright/.auth/teacher.json';

    await page.goto('/login');
    await expect(page.locator('h1')).toContainText('Connexion');

    await page.fill('input[name="email"]', 'jean.martin@test.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.waitForURL('**/dashboard/teacher');

    await page.context().storageState({ path: teacherAuthFile });

    console.log('Authentification enseignant configurée');
});