import { test, expect } from '@playwright/test';
import { NavigationHelper } from '../Helpers';

test.describe('Teacher - Dashboard', () => {
    test('should display teacher dashboard', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoDashboard();
        
        await expect(page).toHaveURL(/dashboard/);
        await nav.expectTestIdVisible('dashboard-content');
    });

    test('should navigate to exams list', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoExams();
        
        await expect(page).toHaveURL(/exams/);
    });

    test('should access create exam page', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoCreateExam();
        
        await expect(page).toHaveURL(/exams\/create/);
    });
});
