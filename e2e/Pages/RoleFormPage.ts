import { Page, Locator } from '@playwright/test';
import { Core } from '../Helpers';

/**
 * Page Object Model for Role Permission Configuration
 */
export class RoleFormPage extends Core {
  readonly submitButton: Locator;
  readonly cancelButton: Locator;

  readonly selectAllButton: Locator;
  readonly deselectAllButton: Locator;

  constructor(page: Page) {
    super(page);

    this.submitButton = this.getByTestId('role-submit-button');
    this.cancelButton = this.getByTestId('role-cancel-button');
    this.selectAllButton = this.getByTestId('permission-select-all');
    this.deselectAllButton = this.getByTestId('permission-deselect-all');
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
}
