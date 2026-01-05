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
    const e2ePort = process.env.E2E_PORT || '8001';

    // Load E2E environment variables
    const envE2EPath = join(rootDir, '.env.e2e');
    const envVars: Record<string, string> = {};
    
    Object.entries(process.env).forEach(([key, value]) => {
        if (value !== undefined) {
            envVars[key] = value;
        }
    });
    
    if (existsSync(envE2EPath)) {
        const envContent = readFileSync(envE2EPath, 'utf-8');
        envContent.split('\n').forEach(line => {
            const match = line.match(/^([^#=]+)=(.*)$/);
            if (match) {
                const key = match[1].trim();
                const value = match[2].trim().replace(/^["'](.*)["']$/, '$1');
                envVars[key] = value;
            }
        });

        console.info('[E2EGlobalSetup] Loaded .env.e2e configuration');
    }

    console.info('[E2EGlobalSetup] Preparing test database...');
  
    try {
        execSync('php artisan e2e:setup', {
            cwd: rootDir,
            stdio: 'inherit',
            env: envVars
        });

        console.info('[E2EGlobalSetup] Test database prepared');

    } catch (error) {

        console.error('[E2EGlobalSetup] Failed to prepare test database:', error);
       
        throw error;
    }

    console.info(`[E2EGlobalSetup] Starting Laravel server on port ${e2ePort}...`);
    
    try {
        serverProcess = spawn('php', ['artisan', 'serve', `--port=${e2ePort}`], {
            cwd: rootDir,
            stdio: 'pipe',
            env: {
                ...envVars,
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