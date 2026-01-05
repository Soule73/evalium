import { test as setup, expect } from '@playwright/test';
import { getStudentCredentials } from '../Helpers/utils';
import { LoginPage } from '../Pages/LoginPage';

const authFile = 'playwright/.auth/student.json';

/**
 * Setup authentication for student user
 */
setup('authenticate as student', async ({ page }) => {
    const loginPage = new LoginPage(page);
    const credentials = getStudentCredentials();
    
    // Navigate to login page
    await loginPage.navigate();
    
    // Login as student with credentials from .env
    await loginPage.loginWith(credentials.email, credentials.password, true);
    
    // Wait for successful login and dashboard
    await expect(page).toHaveURL(/dashboard/);
    
    // Save authenticated state
    await page.context().storageState({ path: authFile });
    });
