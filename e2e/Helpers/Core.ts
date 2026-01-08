import { Page, Locator, expect } from '@playwright/test';

/**
 * Core helper class with reusable utilities for E2E tests
 */
export class Core {
    constructor(protected page: Page) {}

    /**
     * Navigate to a path
     */
    async goto(path: string): Promise<void> {
        await this.page.goto(path);
        await this.page.waitForLoadState('networkidle');
    }

    /**
     * Wait for element to be visible
     */
    async waitForElement(selector: string, timeout = 10000): Promise<Locator> {
        const element = this.page.locator(selector);
        await element.waitFor({ state: 'visible', timeout });
        return element;
    }

    /**
     * Get element by test ID (data-e2e)
     */
    getByTestId(testId: string): Locator {
        return this.page.getByTestId(testId);
    }

    /**
     * Fill input field by test ID
     */
    async fillByTestId(testId: string, value: string): Promise<void> {
        await this.getByTestId(testId).fill(value);
    }

    /**
     * Click element by test ID
     */
    async clickByTestId(testId: string): Promise<void> {
        await this.getByTestId(testId).click();
    }

    /**
     * Wait for navigation to complete
     */
    async waitForNavigation(timeout = 10000): Promise<void> {
        await this.page.waitForLoadState('networkidle', { timeout });
    }

    /**
     * Wait for redirect to a specific URL
     */
    async waitForRedirectTo(url: string, timeout = 10000): Promise<void> {
        await this.page.waitForURL(`**${url}`, { timeout });
    }

    /**
     * Take screenshot with name
     */
    async screenshot(name: string): Promise<void> {
        await this.page.screenshot({ path: `test-results/screenshots/${name}.png`, fullPage: true });
    }

    /**
     * Check if element is visible
     */
    async isVisible(selector: string): Promise<boolean> {
        try {
            await this.page.locator(selector).waitFor({ state: 'visible', timeout: 5000 });
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Wait for text to appear
     */
    async waitForText(text: string, timeout = 10000): Promise<void> {
        await this.page.getByText(text).waitFor({ state: 'visible', timeout });
    }

    /**
     * Check if text is visible
     */
    async isTextVisible(text: string | RegExp): Promise<boolean> {
        try {
            await this.page.getByText(text).waitFor({ state: 'visible', timeout: 5000 });
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Expect text to be visible
     */
    async expectTextVisible(text: string | RegExp): Promise<void> {
        await expect(this.page.getByText(text)).toBeVisible();
    }

    /**
     * Get current URL
     */
    getCurrentUrl(): string {
        return this.page.url();
    }

    /**
     * Check if current URL contains path
     */
    async expectUrlContains(path: string): Promise<void> {
        await expect(this.page).toHaveURL(new RegExp(path));
    }

    /**
     * Check if current URL does not contain path
     */
    async expectUrlNotContains(path: string): Promise<void> {
        await expect(this.page).not.toHaveURL(new RegExp(path));
    }

    /**
     * Check if element with test ID is visible
     */
    async expectTestIdVisible(testId: string): Promise<void> {
        await expect(this.getByTestId(testId)).toBeVisible();
    }

    async waitTestIdState(testId: string, state: "detached" | "attached" | "visible" | "hidden", timeout = 10000): Promise<void> {
        const element = this.getByTestId(testId);
        await element.waitFor({ state, timeout });
    }

    /**
     * Check if element with test ID has text
     */
    async expectTestIdHasText(testId: string, text: string | RegExp): Promise<void> {
        await expect(this.getByTestId(testId)).toHaveText(text);
    }

    /**
     * Wait for API response
     */
    async waitForResponse(urlPattern: string | RegExp, timeout = 10000): Promise<void> {
        await this.page.waitForResponse(urlPattern, { timeout });
    }

    /**
     * Reload page
     */
    async reload(): Promise<void> {
        await this.page.reload({ waitUntil: 'networkidle' });
    }

    /**
     * Clear local storage
     */
    async clearStorage(): Promise<void> {
        await this.page.evaluate(() => {
            localStorage.clear();
            sessionStorage.clear();
        });
    }

    /**
     * Set local storage item
     */
    async setLocalStorage(key: string, value: string): Promise<void> {
        await this.page.evaluate(
            ({ key, value }) => localStorage.setItem(key, value),
            { key, value }
        );
    }

    /**
     * Get local storage item
     */
    async getLocalStorage(key: string): Promise<string | null> {
        return await this.page.evaluate((key) => localStorage.getItem(key), key);
    }
}
