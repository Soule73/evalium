import { defineConfig, devices } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export default defineConfig({
    globalSetup: resolve(__dirname, './global-setup.ts'),
    globalTeardown: resolve(__dirname, './global-teardown.ts'),
    testDir: './',
    testMatch: '**/*.{spec,test}.ts',
    testIgnore: '**/node_modules/**',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [
        ['list'],
        ['html', { open: 'on-failure' }],
        ['json', { outputFile: '../playwright-report/results.json' }],
        ['junit', { outputFile: '../playwright-report/results.xml' }]
    ],
    timeout: 30 * 1000,
    expect: {
        timeout: 10 * 1000,
    },
    use: {
        baseURL: process.env.BASE_URL || `http://localhost:${process.env.E2E_PORT || '8001'}`,
        testIdAttribute: 'data-e2e',
        trace: 'on',
        screenshot: 'on',
        video: 'on',
        acceptDownloads: true,
        ignoreHTTPSErrors: true,
        navigationTimeout: 15 * 1000,
        actionTimeout: 10 * 1000,
    },

    projects: [
        {
            name: 'setup-admin',
            testMatch: /.*auth\.admin\.setup\.ts/,
        },
        {
            name: 'setup-teacher',
            testMatch: /.*auth\.teacher\.setup\.ts/,
        },
        {
            name: 'setup-student',
            testMatch: /.*auth\.student\.setup\.ts/,
        },
        {
            name: 'admin',
            testMatch: '**/admin/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                storageState: '../playwright/.auth/admin.json',
            },
            dependencies: ['setup-admin'],
        },
        {
            name: 'teacher',
            testMatch: '**/teacher/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                storageState: '../playwright/.auth/teacher.json',
            },
            dependencies: ['setup-teacher'],
        },
        {
            name: 'student',
            testMatch: '**/student/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                storageState: '../playwright/.auth/student.json',
            },
            dependencies: ['setup-student'],
        },
    ],

    outputDir: '../test-results/',
});
