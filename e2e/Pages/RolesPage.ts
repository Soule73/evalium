import { Page, Locator, expect } from '@playwright/test';
import { Core } from '../Helpers';

/**
 * Page Object Model for Roles management page
 */
export class RolesPage extends Core {
    static url = '/roles';
    static createUrl = '/roles/create';

    // List page locators
    readonly createButtonEmpty: Locator;
    readonly createButton: Locator;
    readonly searchInput: Locator;

    constructor(page: Page) {
        super(page);

        this.createButtonEmpty = this.getByTestId('role-create-empty');
        this.createButton = this.getByTestId('role-create-button');
        this.searchInput = this.getByTestId('roles-datatable-search-input');
    }

    /**
     * Navigate to roles list page
     */
    async navigate(): Promise<void> {
        await this.goto(RolesPage.url);
    }

    /**
     * Await to redirect to roles list page
     */
    async waitForRedirect(): Promise<void> {
        await this.waitForRedirectTo(RolesPage.url);
    }

    /**
     * Role is visible in list
     */
    async isRoleVisibleByTestId(roleName: string): Promise<void> {
        const roleLocator = this.getByTestId(`role-name-${roleName.toLowerCase()}`);

        await roleLocator.waitFor({ state: 'visible', timeout: 10000 });
        await expect(roleLocator).toHaveText(roleName);
    }

    /**
     * Navigate to create role page
     */
    async navigateToCreate(): Promise<void> {
        await this.goto(RolesPage.createUrl);
    }

    /**
     * Click create button (header or empty state)
     */
    async clickCreate(): Promise<void> {
        const isEmpty = await this.createButtonEmpty.isVisible().catch(() => false);
        if (isEmpty) {
            await this.createButtonEmpty.click();
        } else {
            await this.createButton.click();
        }
        await this.waitForNavigation();
    }

    /**
     * Search for role by name
     */
    async searchRole(query: string): Promise<void> {
        await this.searchInput.fill(query);
        await this.waitForNavigation();
    }

    /**
     * Get delete button for specific role
     */
    getDeleteButton(name: string): Locator {
        return this.getByTestId(`role-delete-${name.toLowerCase()}`);
    }

    /**
     * Click edit button for role
     */
    async clickEdit(name: string): Promise<void> {
        await this.clickByTestId(`role-edit-${name.toLowerCase()}`);
        await this.waitForNavigation();
    }

    /**
     * Click delete button for role
     */
    async clickDelete(name: string): Promise<void> {
        await this.getDeleteButton(name).click();
    }

    /**
     * Check if role exists in list
     */
    async hasRole(roleName: string): Promise<boolean> {
        return await this.getByTestId(`role-name-${roleName.toLowerCase()}`)
            .isVisible()
            .catch(() => false);
    }

    /**
     * Get role row by name
     */
    getRoleRow(roleName: string): Locator {
        return this.page.locator(`text=${roleName}`).locator('..');
    }

    /**
     * Check if delete button exists for role
     */
    async hasDeleteButton(roleName: string): Promise<boolean> {
        const deleteButton = this.getDeleteButton(roleName);
        return await deleteButton.isVisible().catch(() => false);
    }

    /**
     * Expect empty state to be visible
     */
    async expectEmptyState(): Promise<void> {
        await this.expectTestIdVisible('role-create-empty');
    }

    /**
     * Expect role to be visible in list
     * @deprecated Use isRoleVisibleByTestId instead
     */
    async expectRole(roleName: string): Promise<void> {
        await this.expectTextVisible(roleName);
    }

    /**
     * Expect no results message
     */
    async expectNoResults(): Promise<void> {
        await this.expectTestIdVisible('roles-datatable-empty-state');
        await this.expectTestIdVisible('roles-datatable-empty-reset-filters-button');
        await this.expectTestIdVisible('roles-datatable-reset-filters-button');
    }
}
