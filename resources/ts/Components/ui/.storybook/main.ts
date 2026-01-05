import type { StorybookConfig } from '@storybook/react-vite';
import path from 'path';
import tailwindcss from '@tailwindcss/vite';

const config: StorybookConfig = {
    stories: ['../**/*.mdx', '../**/*.stories.@(js|jsx|ts|tsx)'],
    addons: [
        '@storybook/addon-links',
        '@storybook/addon-essentials',
        '@storybook/addon-interactions',
    ],
    framework: {
        name: '@storybook/react-vite',
        options: {},
    },
    viteFinal: async (config) => {
        if (config.resolve) {
            config.resolve.alias = {
                ...config.resolve.alias,
                '@examena/ui': path.resolve(__dirname, '..'),
                '@': path.resolve(__dirname, '../../../'),
            };
        }

        config.plugins = config.plugins || [];
        config.plugins.push(tailwindcss());

        return config;
    },
};

export default config;
