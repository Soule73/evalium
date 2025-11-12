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
            '@': fileURLToPath(new URL('./resources/ts', import.meta.url))
        }
    },
    optimizeDeps: {
        include: ['react', 'react-dom', '@inertiajs/react']
    },
    build: {
        chunkSizeWarningLimit: 800,
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        if (id.includes('react') || id.includes('react-dom')) {
                            return 'react-vendor';
                        }
                        if (id.includes('@inertiajs')) {
                            return 'inertia-vendor';
                        }
                        if (id.includes('@headlessui')) {
                            return 'headlessui-vendor';
                        }
                        if (id.includes('@heroicons')) {
                            return 'heroicons-vendor';
                        }
                        if (id.includes('katex')) {
                            return 'katex-vendor';
                        }
                        if (id.includes('ziggy-js') || id.includes('axios')) {
                            return 'network-vendor';
                        }
                        if (id.includes('marked') || id.includes('react-markdown') || id.includes('remark-') || id.includes('rehype-')) {
                            return 'markdown-vendor';
                        }
                        if (id.includes('prismjs') || id.includes('prism')) {
                            return 'prism-vendor';
                        }
                        if (id.includes('@dnd-kit')) {
                            return 'dnd-vendor';
                        }
                        if (id.includes('easymde')) {
                            return 'editor-vendor';
                        }
                        if (id.includes('clsx') || id.includes('tailwind')) {
                            return 'styling-vendor';
                        }
                        if (id.includes('zod')) {
                            return 'validation-vendor';
                        }
                        return 'vendor';
                    }

                    if (id.includes('resources/ts/Components')) {
                        if (id.includes('Components/shared/datatable') || id.includes('DataTable')) {
                            return 'datatable-components';
                        }
                        if (id.includes('Components/features/exam')) {
                            return 'exam-feature-components';
                        }
                        if (id.includes('Components/features')) {
                            return 'feature-components';
                        }
                        if (id.includes('Components/shared')) {
                            return 'shared-components';
                        }
                        if (id.includes('Components/layout')) {
                            return 'layout-components';
                        }
                        return 'ui-components';
                    }

                    if (id.includes('resources/ts/Pages')) {
                        if (id.includes('Pages/Admin')) {
                            return 'admin-pages';
                        }
                        if (id.includes('Pages/Exam')) {
                            return 'exam-pages';
                        }
                        if (id.includes('Pages/Student')) {
                            return 'student-pages';
                        }
                        if (id.includes('Pages/Roles') || id.includes('Pages/Users') || id.includes('Pages/Groups') || id.includes('Pages/Levels')) {
                            return 'management-pages';
                        }
                        return 'other-pages';
                    }

                    if (id.includes('resources/ts/hooks')) {
                        return 'hooks';
                    }

                    if (id.includes('resources/ts/utils')) {
                        return 'utils';
                    }
                },
            },
        },
    },
});