import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [react()],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./vitest.setup.ts'],
        include: ['resources/ts/**/*.{test,spec}.{ts,tsx}'],
        exclude: ['resources/ts/tests/e2e/**', 'node_modules', 'vendor'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html', 'lcov'],
            include: ['resources/ts/**/*.{ts,tsx}'],
            exclude: [
                'resources/ts/**/*.d.ts',
                'resources/ts/app.tsx',
                'resources/ts/bootstrap.ts',
                'resources/ts/tests/**',
                'resources/ts/**/*.spec.{ts,tsx}',
                'resources/ts/**/*.test.{ts,tsx}',
            ],
            thresholds: {
                branches: 70,
                functions: 70,
                lines: 70,
                statements: 70,
            },
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/ts'),
            '@examena/ui': path.resolve(__dirname, './resources/ts/Components/ui'),
        },
    },
});
