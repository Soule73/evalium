import { Page } from '@playwright/test';
import { Core } from './Core';

export interface LoginCredentials {
    email: string;
    password: string;
    remember?: boolean;
}

/**
 * Authentication helper for E2E tests
 */
export class AuthHelper extends Core {
    constructor(page: Page) {
        super(page);
    }

    /**
     * Navigate to login page
     */
    async gotoLogin(): Promise<void> {
        await this.goto('/login');
    }

    /**
     * Fill login form
     */
    async fillLoginForm(credentials: LoginCredentials): Promise<void> {
        await this.fillByTestId('email-input', credentials.email);
        await this.fillByTestId('password-input', credentials.password);
        
        if (credentials.remember) {
            await this.clickByTestId('remember-checkbox');
        }
    }

    /**
     * Submit login form
     */
    async submitLogin(): Promise<void> {
        await this.clickByTestId('login-submit');
        await this.waitForNavigation();
    }

    /**
     * Login with credentials
     */
    async login(credentials: LoginCredentials): Promise<void> {
        await this.gotoLogin();
        await this.fillLoginForm(credentials);
        await this.submitLogin();
    }

    /**
     * Quick login for admin user
     */
    async loginAsAdmin(): Promise<void> {
        await this.login({
            email: 'admin@example.com',
            password: 'password123',
            remember: true,
        });
    }

    /**
     * Quick login for teacher user
     */
    async loginAsTeacher(): Promise<void> {
        await this.login({
            email: 'teacher@example.com',
            password: 'password123',
            remember: true,
        });
    }

    /**
     * Quick login for student user
     */
    async loginAsStudent(): Promise<void> {
        await this.login({
            email: 'student@example.com',
            password: 'password123',
            remember: true,
        });
    }

    /**
     * Logout user
     */
    async logout(): Promise<void> {
        await this.goto('/logout');
        await this.waitForNavigation();
    }

    /**
     * Check if user is logged in
     */
    async isLoggedIn(): Promise<boolean> {
        return !this.getCurrentUrl().includes('/login');
    }

    /**
     * Wait for dashboard to load
     */
    async waitForDashboard(): Promise<void> {
        await this.expectUrlContains('dashboard');
    }

    /**
     * Verify login success by checking dashboard
     */
    async verifyLoginSuccess(): Promise<void> {
        await this.waitForDashboard();
        await this.expectTestIdVisible('dashboard-content');
    }

    /**
     * Verify login error message
     */
    async verifyLoginError(expectedError?: string): Promise<void> {
        if (expectedError) {
            await this.waitForText(expectedError);
        } else {
            await this.expectTestIdVisible('error-message');
        }
    }
}
