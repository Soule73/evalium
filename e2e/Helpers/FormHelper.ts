import { Page } from '@playwright/test';
import { Core } from './Core';

/**
 * Form helper for E2E tests
 */
export class FormHelper extends Core {
    constructor(page: Page) {
        super(page);
    }

    /**
     * Fill input by label
     */
    async fillByLabel(label: string, value: string): Promise<void> {
        await this.page.getByLabel(label).fill(value);
    }

    /**
     * Fill input by placeholder
     */
    async fillByPlaceholder(placeholder: string, value: string): Promise<void> {
        await this.page.getByPlaceholder(placeholder).fill(value);
    }

    /**
     * Select option in dropdown by test ID
     */
    async selectByTestId(testId: string, value: string): Promise<void> {
        await this.getByTestId(testId).selectOption(value);
    }

    /**
     * Check checkbox by test ID
     */
    async checkByTestId(testId: string): Promise<void> {
        await this.getByTestId(testId).check();
    }

    /**
     * Uncheck checkbox by test ID
     */
    async uncheckByTestId(testId: string): Promise<void> {
        await this.getByTestId(testId).uncheck();
    }

    /**
     * Upload file by test ID
     */
    async uploadFileByTestId(testId: string, filePath: string): Promise<void> {
        await this.getByTestId(testId).setInputFiles(filePath);
    }

    /**
     * Submit form by test ID
     */
    async submitFormByTestId(testId: string): Promise<void> {
        await this.clickByTestId(testId);
        await this.waitForNavigation();
    }

    /**
     * Fill multiple fields by test IDs
     */
    async fillMultiple(fields: Record<string, string>): Promise<void> {
        for (const [testId, value] of Object.entries(fields)) {
            await this.fillByTestId(testId, value);
        }
    }

    /**
     * Get input value by test ID
     */
    async getInputValue(testId: string): Promise<string> {
        return await this.getByTestId(testId).inputValue();
    }

    /**
     * Check if checkbox is checked
     */
    async isChecked(testId: string): Promise<boolean> {
        return await this.getByTestId(testId).isChecked();
    }

    /**
     * Wait for form to be submitted (loading state)
     */
    async waitForSubmission(): Promise<void> {
        await this.page.waitForLoadState('networkidle');
    }

    /**
     * Check if field has error
     */
    async hasError(testId: string): Promise<boolean> {
        const errorElement = this.page.locator(`[data-e2e="${testId}-error"]`);
        return await errorElement.isVisible();
    }

    /**
     * Get error message for field
     */
    async getErrorMessage(testId: string): Promise<string | null> {
        const errorElement = this.page.locator(`[data-e2e="${testId}-error"]`);
        if (await errorElement.isVisible()) {
            return await errorElement.textContent();
        }
        return null;
    }

    /**
     * Clear form field
     */
    async clearField(testId: string): Promise<void> {
        await this.getByTestId(testId).clear();
    }
}
