import { Page, Locator } from '@playwright/test';
import { AuthHelper } from '../Helpers';

/**
 * Page Object Model for Login page
 */
export class LoginPage extends AuthHelper {
    static url = '/login';

    // Locators
    readonly emailInput: Locator;
    readonly passwordInput: Locator;
    readonly rememberCheckbox: Locator;
    readonly submitButton: Locator;
    readonly errorMessage: Locator;
    readonly forgotPasswordLink: Locator;

    constructor(page: Page) {
        super(page);
        
        // Initialize locators using data-e2e test IDs
        this.emailInput = this.getByTestId('email-input');
        this.passwordInput = this.getByTestId('password-input');
        this.rememberCheckbox = this.getByTestId('remember-checkbox');
        this.submitButton = this.getByTestId('login-submit');
        this.errorMessage = this.getByTestId('error-message');
        this.forgotPasswordLink = this.getByTestId('forgot-password-link');
    }

    /**
     * Navigate to login page
     */
    async navigate(): Promise<void> {
        await this.goto(LoginPage.url);
    }

    /**
     * Fill email field
     */
    async fillEmail(email: string): Promise<void> {
        await this.emailInput.fill(email);
    }

    /**
     * Fill password field
     */
    async fillPassword(password: string): Promise<void> {
        await this.passwordInput.fill(password);
    }

    /**
     * Toggle remember me checkbox
     */
    async toggleRememberMe(): Promise<void> {
        await this.rememberCheckbox.click();
    }

    /**
     * Submit the login form
     */
    async submit(): Promise<void> {
        await this.submitButton.click();
        await this.waitForNavigation();
    }

    /**
     * Complete login flow
     */
    async loginWith(email: string, password: string, remember = false): Promise<void> {
        await this.fillEmail(email);
        await this.fillPassword(password);
        
        if (remember) {
            await this.toggleRememberMe();
        }
        
        await this.submit();
    }

    /**
     * Check if error message is visible
     */
    async hasError(): Promise<boolean> {
        try {
            return await this.errorMessage.isVisible();
        } catch {
            return false;
        }
    }

    /**
     * Get error message text
     */
    async getErrorText(): Promise<string | null> {
        if (await this.hasError()) {
            return await this.errorMessage.textContent();
        }
        return null;
    }

    /**
     * Click forgot password link
     */
    async clickForgotPassword(): Promise<void> {
        await this.forgotPasswordLink.click();
        await this.waitForNavigation();
    }

    /**
     * Check if submit button is disabled
     */
    async isSubmitDisabled(): Promise<boolean> {
        return await this.submitButton.isDisabled();
    }

    /**
     * Check if on login page
     */
    async isOnLoginPage(): Promise<boolean> {
        return this.getCurrentUrl().includes('/login');
    }
}

