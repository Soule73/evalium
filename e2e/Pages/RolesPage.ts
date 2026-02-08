import { Page, Locator, expect } from '@playwright/test';
import { Core } from '../Helpers';

/**
 * Page Object Model for Roles configuration page
 */
export class RolesPage extends Core {
    static url = '/admin/roles';

    readonly searchInput: Locator;

    constructor(page: Page) {
        super(page);

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
     * Search for role by name
     */
    async searchRole(query: string): Promise<void> {
        await this.searchInput.fill(query);
        await this.waitForNavigation();
    }

    /**
     * Click edit button for role
     */
    async clickEdit(name: string): Promise<void> {
        await this.clickByTestId(`role-edit-${name.toLowerCase()}`);
        await this.waitForNavigation();
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
     * Expect no results message
     */
    async expectNoResults(): Promise<void> {
        await this.expectTestIdVisible('roles-datatable-empty-state');
        await this.expectTestIdVisible('roles-datatable-empty-reset-filters-button');
        await this.expectTestIdVisible('roles-datatable-reset-filters-button');
    }
}
