import { execSync, spawn, ChildProcess } from 'child_process';
import { existsSync, mkdirSync, writeFileSync, readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

let serverProcess: ChildProcess | null = null;

async function globalSetup() {
    console.info('[E2EGlobalSetup] Starting E2E test setup...');

    const rootDir = join(__dirname, '..');
    const pidFile = join(__dirname, '.laravel-server.pid');

    // Load E2E environment variables from .env.testing (Laravel convention)
    const envTestingPath = join(rootDir, '.env.testing');
    const envVars: Record<string, string> = {};
    
    Object.entries(process.env).forEach(([key, value]) => {
        if (value !== undefined) {
            envVars[key] = value;
        }
    });
    
    if (existsSync(envTestingPath)) {
        const envContent = readFileSync(envTestingPath, 'utf-8');
        envContent.split('\n').forEach(line => {
            const match = line.match(/^([^#=]+)=(.*)$/);
            if (match) {
                const key = match[1].trim();
                const value = match[2].trim().replace(/^["'](.*)["']$/, '$1');
                envVars[key] = value;
                // Also set in process.env for Playwright config
                process.env[key] = value;
            }
        });

        console.info('[E2EGlobalSetup] Loaded .env.testing configuration');
    }

    // Extract port from APP_URL or use default
    const appUrl = envVars['APP_URL'] || process.env.APP_URL || 'http://localhost:8001';
    const e2ePort = new URL(appUrl).port || '8001';
    process.env.E2E_PORT = e2ePort;
    process.env.BASE_URL = appUrl;

    console.info('[E2EGlobalSetup] Preparing test database...');
  
    try {
        execSync('php artisan e2e:setup --env=testing', {
            cwd: rootDir,
            stdio: 'inherit',
            env: { ...process.env, APP_ENV: 'testing' }
        });

        console.info('[E2EGlobalSetup] Test database prepared');

    } catch (error) {

        console.error('[E2EGlobalSetup] Failed to prepare test database:', error);
       
        throw error;
    }

    console.info('[E2EGlobalSetup] Building frontend assets...');
    
    try {
        execSync('yarn build', {
            cwd: rootDir,
            stdio: 'inherit',
            env: envVars
        });
        
        console.info('[E2EGlobalSetup] Frontend assets built successfully');
    } catch (error) {
        console.error('[E2EGlobalSetup] Failed to build frontend assets:', error);
        throw error;
    }

    console.info(`[E2EGlobalSetup] Starting Laravel server on port ${e2ePort}...`);
    
    try {
        serverProcess = spawn('php', ['artisan', 'serve', `--port=${e2ePort}`], {
            cwd: rootDir,
            stdio: 'pipe',
            env: {
                ...process.env,
                ...envVars,
                APP_ENV: 'testing',
                APP_URL: `http://localhost:${e2ePort}`,
            },
            detached: false,
            shell: true
        });

        if (serverProcess.pid) {
            writeFileSync(pidFile, serverProcess.pid.toString());
        }

        serverProcess.stdout?.on('data', (data) => {
            console.log(`[Laravel] ${data.toString().trim()}`);
        });

        serverProcess.stderr?.on('data', (data) => {
            console.error(`[Laravel Error] ${data.toString().trim()}`);
        });

        await new Promise((resolve) => setTimeout(resolve, 3000));
        
        console.info(`[E2EGlobalSetup] Laravel server started on http://localhost:${e2ePort}`);

    } catch (error) {
        console.error('[E2EGlobalSetup] Failed to start Laravel server:', error);
        
        throw error;
    }

    const authDir = join(rootDir, 'playwright', '.auth');
    
    if (!existsSync(authDir)) {
        mkdirSync(authDir, { recursive: true });
    }

    console.info('[E2EGlobalSetup] E2E setup complete');
}

export default globalSetup;