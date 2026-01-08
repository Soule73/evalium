import { Page, Locator } from '@playwright/test';
import { Core } from '../Helpers';

/**
 * Page Object Model for Role Form (Create/Edit)
 */
export class RoleFormPage extends Core {
    // Form locators
    readonly nameInput: Locator;
    readonly submitButton: Locator;
    readonly cancelButton: Locator;
    
    // Permission selection locators
    readonly selectAllButton: Locator;
    readonly deselectAllButton: Locator;
    readonly syncButton: Locator;

    constructor(page: Page) {
        super(page);
        
        this.nameInput = this.getByTestId('role-name-input');
        this.submitButton = this.getByTestId('role-submit-button');
        this.cancelButton = this.getByTestId('role-cancel-button');
        this.selectAllButton = this.getByTestId('permission-select-all');
        this.deselectAllButton = this.getByTestId('permission-deselect-all');
        this.syncButton = this.getByTestId('permission-sync-button');
    }

    /**
     * Fill role name
     */
    async fillName(name: string): Promise<void> {
        await this.nameInput.fill(name);
    }

    /**
     * Get role name value
     */
    async getName(): Promise<string> {
        return await this.nameInput.inputValue();
    }

    /**
     * Check if name input is disabled (system role)
     */
    async isNameDisabled(): Promise<boolean> {
        return await this.nameInput.isDisabled();
    }

    /**
     * Select all permissions
     */
    async selectAllPermissions(): Promise<void> {
        await this.selectAllButton.click();
    }

    /**
     * Deselect all permissions
     */
    async deselectAllPermissions(): Promise<void> {
        await this.deselectAllButton.click();
    }

    /**
     * Sync permissions for system role
     */
    async syncPermissions(): Promise<void> {
        if (await this.syncButton.isVisible()) {
            await this.syncButton.click();
            await this.waitForNavigation();
        }
    }

    /**
     * Get permission checkbox by ID
     */
    getPermissionCheckbox(permissionId: number): Locator {
        return this.getByTestId(`permission-checkbox-${permissionId}`);
    }

    /**
     * Toggle specific permission
     */
    async togglePermission(permissionId: number): Promise<void> {
        await this.getPermissionCheckbox(permissionId).click();
    }

    /**
     * Check if permission is selected
     */
    async isPermissionSelected(permissionId: number): Promise<boolean> {
        return await this.getPermissionCheckbox(permissionId).isChecked();
    }

    /**
     * Get count of selected permissions
     */
    async getSelectedPermissionsCount(): Promise<number> {
        const checkboxes = this.page.locator('[data-e2e^="permission-checkbox-"]:checked');
        return await checkboxes.count();
    }

    /**
     * Get count of all permissions
     */
    async getAllPermissionsCount(): Promise<number> {
        const checkboxes = this.page.locator('[data-e2e^="permission-checkbox-"]');
        return await checkboxes.count();
    }

    /**
     * Submit form
     */
    async submit(): Promise<void> {
        await this.submitButton.click();
        await this.waitForNavigation();
    }

    /**
     * Cancel form
     */
    async cancel(): Promise<void> {
        await this.cancelButton.click();
        await this.waitForNavigation();
    }

    /**
     * Check if sync button is visible (system role indicator)
     */
    async isSyncButtonVisible(): Promise<boolean> {
        return await this.syncButton.isVisible().catch(() => false);
    }

    /**
     * Expect validation error
     */
    async expectValidationError(pattern: string | RegExp): Promise<void> {
        await this.expectTextVisible(pattern);
    }
}
