import { test, expect } from '@playwright/test';
import { RolesPage, RoleFormPage } from '../Pages';

test.describe('Admin - Roles Configuration', () => {
    const SYSTEM_ROLES = ['admin', 'teacher', 'student', 'super_admin'];
    let rolesPage: RolesPage;
    let roleFormPage: RoleFormPage;

    test.beforeEach(async ({ page }) => {
        rolesPage = new RolesPage(page);
        roleFormPage = new RoleFormPage(page);
    });

    test('should display roles list with all system roles', async () => {
        await rolesPage.navigate();

        for (const roleName of SYSTEM_ROLES) {
            expect(await rolesPage.hasRole(roleName)).toBe(true);
        }
    });

    test('should display permissions count for each role', async ({ page }) => {
        await rolesPage.navigate();

        const permissionCounts = page.locator('[data-e2e^="role-permissions-"]');
        const count = await permissionCounts.count();

        expect(count).toBeGreaterThan(0);
    });

    test('should navigate to edit page for system role', async ({ page }) => {
        await rolesPage.navigate();

        const editButton = page.locator('[data-e2e="role-edit-teacher"]');
        await editButton.click();
        await roleFormPage.waitForNavigation();

        await rolesPage.expectUrlContains('/admin/roles/');
        await rolesPage.expectUrlContains('/edit');
    });

    test('should display role name as title on edit page', async ({ page }) => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        const pageTitle = page.locator('h1');
        await expect(pageTitle).toContainText('teacher', { ignoreCase: true });
    });

    test('should toggle permissions on edit page', async ({ page }) => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        const initialCount = await roleFormPage.getSelectedPermissionsCount();

        await roleFormPage.togglePermission(1);

        const newCount = await roleFormPage.getSelectedPermissionsCount();
        expect(newCount).not.toBe(initialCount);
    });

    test('should select all permissions', async () => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        await roleFormPage.selectAllPermissions();

        const selectedCount = await roleFormPage.getSelectedPermissionsCount();
        const totalCount = await roleFormPage.getAllPermissionsCount();

        expect(selectedCount).toBe(totalCount);
    });

    test('should deselect all permissions', async () => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        await roleFormPage.selectAllPermissions();
        let checkedCount = await roleFormPage.getSelectedPermissionsCount();
        expect(checkedCount).toBeGreaterThan(0);

        await roleFormPage.deselectAllPermissions();
        checkedCount = await roleFormPage.getSelectedPermissionsCount();
        expect(checkedCount).toBe(0);
    });

    test('should save permission changes', async () => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        await roleFormPage.selectAllPermissions();
        await roleFormPage.submit();

        await rolesPage.waitForRedirect();
    });

    test('should cancel permission changes', async () => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        await roleFormPage.selectAllPermissions();
        await roleFormPage.cancel();

        await rolesPage.waitForRedirect();
    });

    test('should search for role by name', async () => {
        await rolesPage.navigate();
        await rolesPage.searchRole('teacher');

        await rolesPage.expectTestIdVisible('role-name-teacher');
    });

    test('should display no results when search matches nothing', async ({ page }) => {
        await rolesPage.navigate();
        await rolesPage.searchRole('NonExistentRole12345XYZ');

        await rolesPage.waitTestIdState('datatable-loading-indicator', 'detached');
        await rolesPage.expectUrlContains('search=NonExistentRole12345XYZ');
        await rolesPage.expectNoResults();

        await rolesPage.clickByTestId('roles-datatable-empty-reset-filters-button');
        await rolesPage.waitForNavigation();

        await rolesPage.waitTestIdState('datatable-loading-indicator', 'detached');
        await rolesPage.waitTestIdState('roles-datatable-body', 'visible');

        const url = page.url();
        expect(url).not.toContain('NonExistentRole12345XYZ');
    });

    test('should show validation error when no permissions selected', async () => {
        await rolesPage.navigate();
        await rolesPage.clickEdit('teacher');

        await roleFormPage.deselectAllPermissions();
        await roleFormPage.submitButton.click();

        await roleFormPage.expectTestIdVisible('permission-selector-error');
    });
});