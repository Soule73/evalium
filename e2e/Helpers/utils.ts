import { config } from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// Get current file path in ESM
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load .env from e2e directory
config({ path: path.resolve(__dirname, '../.env') });

export interface TestCredentials {
    email: string;
    password: string;
}

export interface TestConfig {
    baseUrl: string;
    timeout: number;
    expectTimeout: number;
    navigationTimeout: number;
    actionTimeout: number;
    headless: boolean;
    slowMo: number;
    trace: 'on' | 'off' | 'retain-on-failure';
    screenshot: 'on' | 'off' | 'only-on-failure';
    video: 'on' | 'off' | 'retain-on-failure';
    debug: boolean;
}

/**
 * Get admin credentials from environment variables
 */
export function getAdminCredentials(): TestCredentials {
    if(!process.env.ADMIN_EMAIL || !process.env.ADMIN_PASSWORD) {
        throw new Error('Admin credentials are not set in environment variables');
    }
    return {
        email: process.env.ADMIN_EMAIL,
        password: process.env.ADMIN_PASSWORD,
    };
}

/**
 * Get teacher credentials from environment variables
 */
export function getTeacherCredentials(): TestCredentials {
    if(!process.env.TEACHER_EMAIL || !process.env.TEACHER_PASSWORD) {
        throw new Error('Teacher credentials are not set in environment variables');
    }
    return {
        email: process.env.TEACHER_EMAIL,
        password: process.env.TEACHER_PASSWORD,
    };
}

/**
 * Get student credentials from environment variables
 */
export function getStudentCredentials(): TestCredentials {
    if(!process.env.STUDENT_EMAIL || !process.env.STUDENT_PASSWORD) {
        throw new Error('Student credentials are not set in environment variables');
    }
    return {
        email: process.env.STUDENT_EMAIL,
        password: process.env.STUDENT_PASSWORD,
    };
}

/**
 * Get test configuration from environment variables
 */
export function getTestConfig(): TestConfig {
    return {
        baseUrl: process.env.BASE_URL || 'http://localhost:8001',
        timeout: parseInt(process.env.TEST_TIMEOUT || '30000', 10),
        expectTimeout: parseInt(process.env.EXPECT_TIMEOUT || '10000', 10),
        navigationTimeout: parseInt(process.env.NAVIGATION_TIMEOUT || '15000', 10),
        actionTimeout: parseInt(process.env.ACTION_TIMEOUT || '10000', 10),
        headless: process.env.HEADLESS !== 'false',
        slowMo: parseInt(process.env.SLOWMO || '0', 10),
        trace: (process.env.TRACE as 'on' | 'off' | 'retain-on-failure') || 'on',
        screenshot: (process.env.SCREENSHOT as 'on' | 'off' | 'only-on-failure') || 'on',
        video: (process.env.VIDEO as 'on' | 'off' | 'retain-on-failure') || 'on',
        debug: process.env.DEBUG === 'true',
    };
}

/**
 * Get base URL for the application
 */
export function getBaseUrl(): string {
    return process.env.BASE_URL || 'http://localhost:8001';
}

/**
 * Wait for a specific amount of time
 */
export async function wait(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Generate a random string for unique test data
 */
export function randomString(length: number = 10): string {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Generate a random email address
 */
export function randomEmail(): string {
    return `test_${randomString(8)}@example.com`;
}

/**
 * Format date for test data
 */
export function formatDate(date: Date = new Date()): string {
    return date.toISOString().split('T')[0];
}