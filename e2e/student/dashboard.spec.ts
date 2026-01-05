import { test, expect } from '@playwright/test';
import { NavigationHelper } from '../Helpers';

test.describe('Student - Dashboard', () => {
    test('should display student dashboard', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoDashboard();
        
        await expect(page).toHaveURL(/dashboard/);
        await nav.expectTestIdVisible('dashboard-content');
    });

    test('should navigate to student exams', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoStudentExams();
        
        await expect(page).toHaveURL(/student\/exams/);
    });

    test('should not access admin routes', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.goto('/users');
        
        // Should be redirected or show unauthorized
        await expect(page).not.toHaveURL(/users/);
    });
});
