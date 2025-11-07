import { defineConfig, devices } from '@playwright/test';

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
    testDir: './resources/ts/tests/e2e',
    /* Run tests in files in parallel */
    fullyParallel: true,
    /* Fail the build on CI if you accidentally left test.only in the source code. */
    forbidOnly: !!process.env.CI,
    /* Retry on CI only */
    retries: process.env.CI ? 2 : 0,
    /* Opt out of parallel tests on CI. */
    workers: process.env.CI ? 1 : undefined,
    /* Reporter to use. See https://playwright.dev/docs/test-reporters */
    reporter: [
        ['list'],
        ['html', { open: 'on-failure' }],
        ['json', { outputFile: 'playwright-report/results.json' }],
        ['junit', { outputFile: 'playwright-report/results.xml' }]
    ],
    /* Global timeout for each test */
    timeout: 30 * 1000,
    /* Global timeout for expect() assertions */
    expect: {
        timeout: 10 * 1000,
    },
    /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
    use: {
        /* Base URL to use in actions like `await page.goto('/')`. */
        baseURL: process.env.BASE_URL || 'http://localhost:8000',

        /* Collect trace in all cases for better debugging */
        trace: 'on',

        /* Take screenshot in all cases */
        screenshot: 'on',

        /* Record video in all cases */
        video: 'on',

        /* Accept downloads and handle them */
        acceptDownloads: true,

        /* Ignore certificate errors */
        ignoreHTTPSErrors: true,

        /* Default navigation timeout */
        navigationTimeout: 15 * 1000,

        /* Default action timeout */
        actionTimeout: 10 * 1000,
    },

    /* Configure projects for major browsers */
    projects: [
        // Setup project to run before all tests
        {
            name: 'setup',
            testMatch: /.*\.setup\.ts/,
        },

        // Desktop browsers
        {
            name: 'chromium',
            // Exclure les tests d'auth (gérés par chromium-no-auth)
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        // Desktop browsers sans authentification pour les tests de login
        {
            name: 'chromium-no-auth',
            testMatch: '**/auth.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                // Pas de storageState pour tester l'authentification
            },
        },

        {
            name: 'firefox',
            // Exclure les tests d'auth
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['Desktop Firefox'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        {
            name: 'webkit',
            // Exclure les tests d'auth
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['Desktop Safari'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        // Mobile viewports
        {
            name: 'Mobile Chrome',
            // Exclure les tests d'auth
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['Pixel 5'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'Mobile Safari',
            // Exclure les tests d'auth
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['iPhone 12'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        // Branded browsers (only run if available)
        {
            name: 'Microsoft Edge',
            // Exclure les tests d'auth
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['Desktop Edge'],
                channel: 'msedge',
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'Google Chrome',
            // Exclure les tests d'auth
            testIgnore: '**/auth.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                channel: 'chrome',
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        // API testing project
        {
            name: 'api',
            testMatch: /.*api.*\.spec\.ts/,
            use: {
                baseURL: process.env.API_URL || 'http://localhost:8000/api',
            },
        },

        // Performance testing project
        {
            name: 'performance',
            testMatch: /.*performance.*\.spec\.ts/,
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },

        // Load testing project (single worker)
        {
            name: 'load',
            testMatch: /.*load.*\.spec\.ts/,
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
            fullyParallel: false,
        },

        // Accessibility testing project
        {
            name: 'accessibility',
            testMatch: /.*accessibility.*\.spec\.ts/,
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
    ],

    /* Use existing Laravel server - no webServer configuration needed */

    /* Output directory for test results */
    outputDir: 'test-results/',

    /* Global setup and teardown */
    globalSetup: './resources/ts/tests/e2e/setup/global-setup.ts',
    globalTeardown: './resources/ts/tests/e2e/setup/global-teardown.ts',
});