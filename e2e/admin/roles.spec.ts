import { test, expect } from '@playwright/test';
import { RolesPage, RoleFormPage } from '../Pages';

test.describe('Admin - Roles Management', () => {
    const ROLES_CREATE_URL = '/roles/create';
    const SYSTEM_ROLES = ['admin', 'teacher', 'student', 'super_admin'];
    let rolesPage: RolesPage;
    let roleFormPage: RoleFormPage;

    test.beforeEach(async ({ page }) => {
        rolesPage = new RolesPage(page);
        roleFormPage = new RoleFormPage(page);
    });

    test('should display roles list', async () => {
        await rolesPage.navigate();

        await rolesPage.expectTestIdVisible('role-create-button');
    });

    test('should navigate to create role page', async () => {        
        await rolesPage.navigate();
        await rolesPage.clickCreate();
        
        await rolesPage.expectUrlContains(ROLES_CREATE_URL);
    });

    test.describe.serial('CRUD operations on single test role', () => {
        const testRoleName = `Test Role E2E ${Date.now()}`;
        let updatedRoleName: string;

        test('should create a new role with permissions', async () => {
            await rolesPage.navigateToCreate();

            await roleFormPage.fillName(testRoleName);
            await roleFormPage.togglePermission(1);
            await roleFormPage.togglePermission(2);

            await roleFormPage.submit();

            await rolesPage.waitForRedirect();
            await rolesPage.isRoleVisibleByTestId(testRoleName);
        });

        test('should edit the test role', async () => {
            await rolesPage.navigate();
            
            await rolesPage.clickEdit(testRoleName);
            await roleFormPage.waitForNavigation();

            const currentName = await roleFormPage.getName();
            updatedRoleName = `${currentName} Updated`;
            
            await roleFormPage.fillName(updatedRoleName);
            await roleFormPage.togglePermission(3);
            await roleFormPage.submit();
            
            await rolesPage.isRoleVisibleByTestId(updatedRoleName);
        });

        test('should find the test role via search', async () => {
            await rolesPage.navigate();
            await rolesPage.searchRole(updatedRoleName);
            await rolesPage.expectTestIdVisible(`role-name-${updatedRoleName.toLowerCase()}`);
        });

        test('should delete the test role', async ({ }) => {
            await rolesPage.navigate();
            
            await rolesPage.clickDelete(updatedRoleName);

            await roleFormPage.clickByTestId(`role-delete-confirm-button-${updatedRoleName.toLowerCase()}`);
            await roleFormPage.waitForNavigation();

            await rolesPage.waitTestIdState(`role-delete-modal-${updatedRoleName.toLowerCase()}`, 'detached');

            expect(await rolesPage.hasRole(updatedRoleName)).toBe(false);
        });
    });

    test('should deselect all permissions', async () => {        
        await roleFormPage.goto(ROLES_CREATE_URL);

        await roleFormPage.fillName('Test Role Minimal');
        await roleFormPage.selectAllPermissions();

        let checkedCount = await roleFormPage.getSelectedPermissionsCount();
        expect(checkedCount).toBeGreaterThan(0);

        await roleFormPage.deselectAllPermissions();

        checkedCount = await roleFormPage.getSelectedPermissionsCount();
        expect(checkedCount).toBe(0);
    });

    test('should select individual permissions', async () => {        
        await roleFormPage.goto(ROLES_CREATE_URL);

        await roleFormPage.fillName('Test Role Custom');

        await roleFormPage.togglePermission(1);
        expect(await roleFormPage.isPermissionSelected(1)).toBe(true);

        await roleFormPage.togglePermission(2);
        expect(await roleFormPage.isPermissionSelected(2)).toBe(true);
    });

    test('should not allow editing system role name', async ({ page }) => {        
        await rolesPage.navigate();

        const editButton = page.locator('[data-e2e^="role-edit-"]').first();
        await editButton.click();
        await roleFormPage.waitForNavigation();

        const currentName = await roleFormPage.getName();
                
        if (SYSTEM_ROLES.includes(currentName)) {
            expect(await roleFormPage.isNameDisabled()).toBe(true);
        }
    });

    test('should sync permissions for system roles', async ({ page }) => {        
        await rolesPage.navigate();

        const editButtons = page.locator('[data-e2e^="role-edit-"]');
        const count = await editButtons.count();
        
        for (let i = 0; i < count; i++) {
            await rolesPage.navigate();
            const editButton = page.locator('[data-e2e^="role-edit-"]').nth(i);
            await editButton.click();
            await roleFormPage.waitForNavigation();
            
            const currentName = await roleFormPage.getName();
            const systemRoles = ['admin', 'teacher', 'student', 'super_admin'];
            
            if (systemRoles.includes(currentName) && await roleFormPage.isSyncButtonVisible()) {
                await roleFormPage.syncPermissions();
                break;
            }
        }
    });

    test('should not show delete button for system roles', async () => {        
        await rolesPage.navigate();
        
        for (const roleName of SYSTEM_ROLES) {
            expect(await rolesPage.hasDeleteButton(roleName)).toBe(false);
        }
    });

    test('should cancel role creation', async () => {        
        await rolesPage.navigateToCreate();
        await roleFormPage.fillName('Role to Cancel');
        await roleFormPage.cancel();

        await rolesPage.waitForRedirect();
    });

    test('should show validation error for empty role name', async () => {        
        await roleFormPage.goto(ROLES_CREATE_URL);
        await roleFormPage.selectAllPermissions();
        await roleFormPage.submitButton.click();

        await roleFormPage.expectTestIdVisible('role-name-input-error');
    });

    test('should show validation error for no permissions selected', async () => {        
        await roleFormPage.goto(ROLES_CREATE_URL);
        await roleFormPage.fillName('Role Without Permissions');
        await roleFormPage.submitButton.click();

        await roleFormPage.expectTestIdVisible('permission-selector-error');
    });

    test('should display permissions count for each role', async ({ page }) => {
        
        await rolesPage.navigate();

        const permissionCounts = page.locator('[data-e2e^="role-permissions-"]');

        const count = await permissionCounts.count();
        
        expect(count).toBeGreaterThan(0);
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
});


