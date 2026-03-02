import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/ts/app.tsx',
            refresh: true,
        }),
        tailwindcss(),
    ],
    esbuild: {
        jsx: 'automatic',
        jsxImportSource: 'react',
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/ts', import.meta.url)),
            '@evalium/ui': fileURLToPath(new URL('./resources/ts/packages/ui', import.meta.url)),
            '@evalium/utils': fileURLToPath(new URL('./resources/ts/packages/utils', import.meta.url)),
        }
    },
    optimizeDeps: {
        include: ['react', 'react-dom', '@inertiajs/react']
    },
    build: {
        chunkSizeWarningLimit: 800,
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
});