import { test as setup, expect } from '@playwright/test';
import { getTeacherCredentials } from '../Helpers/utils';
import { LoginPage } from '../Pages/LoginPage';

const authFile = 'playwright/.auth/teacher.json';

/**
 * Setup authentication for teacher user
 */
setup('authenticate as teacher', async ({ page }) => {
    const loginPage = new LoginPage(page);
    const credentials = getTeacherCredentials();
    
    // Navigate to login page
    await loginPage.navigate();
    
    // Login as teacher with credentials from .env
    await loginPage.loginWith(credentials.email, credentials.password, true);
    
    // Wait for successful login and dashboard
    await expect(page).toHaveURL(/dashboard/);
    
    // Save authenticated state
    await page.context().storageState({ path: authFile });
});
