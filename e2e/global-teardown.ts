import { execSync } from 'child_process';
import { existsSync, readFileSync, unlinkSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

async function globalTeardown() {
    console.info('[GlobalTeardown] Starting E2E test teardown...');

    const rootDir = join(__dirname, '..');
    const pidFile = join(__dirname, '.laravel-server.pid');

    if (existsSync(pidFile)) {
        try {
            const pid = readFileSync(pidFile, 'utf-8').trim();
            console.info(`[GlobalTeardown] Stopping Laravel server (PID: ${pid})...`);
            
            if (process.platform === 'win32') {
                execSync(`taskkill /PID ${pid} /F /T`, { stdio: 'ignore' });
            } else {
                execSync(`kill -9 ${pid}`, { stdio: 'ignore' });
            }
            
            unlinkSync(pidFile);
            console.info('[GlobalTeardown] Laravel server stopped');
        } catch (error) {
            console.warn('[GlobalTeardown] Failed to stop Laravel server:', error);
        }
    }

    console.info('[GlobalTeardown] Cleaning up test data...');
    try {
        execSync('php artisan e2e:teardown', {
            cwd: rootDir,
            stdio: 'inherit',
            env: { ...process.env, APP_ENV: 'testing' }
        });
        console.info('[GlobalTeardown] Test data cleaned');
    } catch (error) {
        console.warn('[GlobalTeardown] Failed to clean test data:', error);
    }

    console.info('[GlobalTeardown] E2E teardown complete');
}

export default globalTeardown;
