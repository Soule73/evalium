import { test as setup, expect } from '@playwright/test';
import { getAdminCredentials } from '../Helpers/utils';
import { LoginPage } from '../Pages/LoginPage';

const authFile = 'playwright/.auth/admin.json';

/**
 * Setup authentication for admin user
 */
setup('authenticate as admin', async ({ page }) => {
    const loginPage = new LoginPage(page);
    const credentials = getAdminCredentials();
    
    // Navigate to login page
    await loginPage.navigate();
    
    // Login as admin with credentials from .env
    await loginPage.loginWith(credentials.email, credentials.password, true);
    
    // Wait for successful login and dashboard
    await expect(page).toHaveURL(/dashboard/);
    
    // Save authenticated state
    await page.context().storageState({ path: authFile });
    
});
