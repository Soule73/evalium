import { test, expect } from '@playwright/test';
import { NavigationHelper } from '../Helpers';

test.describe('Admin - Dashboard', () => {
    test('should display admin dashboard', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoDashboard();
        
        await expect(page).toHaveURL(/dashboard/);
        await nav.expectTestIdVisible('dashboard-content');
    });

    test('should navigate to users management', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoUsers();
        
        await expect(page).toHaveURL(/users/);
    });

    test('should navigate to roles management', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoRoles();
        
        await expect(page).toHaveURL(/roles/);
    });

    test('should navigate to groups management', async ({ page }) => {
        const nav = new NavigationHelper(page);
        
        await nav.gotoGroups();
        
        await expect(page).toHaveURL(/groups/);
    });
});
