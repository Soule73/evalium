import { test as base } from '@playwright/test';
import { LoginPage } from '../Pages';
import { Core, AuthHelper, NavigationHelper, FormHelper } from '../Helpers';
import {
    getAdminCredentials,
    getTeacherCredentials,
    getStudentCredentials,
    TestCredentials,
} from './utils';

export interface TestFixtures {
    core: Core;
    authHelper: AuthHelper;
    navigationHelper: NavigationHelper;
    formHelper: FormHelper;
    loginPage: LoginPage;
    adminCredentials: TestCredentials;
    teacherCredentials: TestCredentials;
    studentCredentials: TestCredentials;
}

/**
 * Extended test fixtures with all helper classes and credentials
 */
export const test = base.extend<TestFixtures>({
    core: async ({ page }, use) => {
        const core = new Core(page);
        await use(core);
    },

    authHelper: async ({ page }, use) => {
        const authHelper = new AuthHelper(page);
        await use(authHelper);
    },

    navigationHelper: async ({ page }, use) => {
        const navigationHelper = new NavigationHelper(page);
        await use(navigationHelper);
    },

    formHelper: async ({ page }, use) => {
        const formHelper = new FormHelper(page);
        await use(formHelper);
    },

    loginPage: async ({ page }, use) => {
        const loginPage = new LoginPage(page);
        await use(loginPage);
    },

    adminCredentials: async ({}, use) => {
        await use(getAdminCredentials());
    },

    teacherCredentials: async ({}, use) => {
        await use(getTeacherCredentials());
    },

    studentCredentials: async ({}, use) => {
        await use(getStudentCredentials());
    },
});

export { expect } from '@playwright/test';