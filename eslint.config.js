import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import prettier from 'eslint-config-prettier';

export default tseslint.config(
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/**',
            'storage/**',
            'dist/**',
            '*.d.ts',
            'playwright/**',
            'playwright-report/**',
            'test-results/**',
            'coverage/**',
        ],
    },

    js.configs.recommended,
    ...tseslint.configs.recommended,

    {
        files: ['resources/ts/**/*.{ts,tsx}'],
        plugins: {
            react,
            'react-hooks': reactHooks,
            'react-refresh': reactRefresh,
        },
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'module',
            parserOptions: {
                ecmaFeatures: { jsx: true },
            },
        },
        settings: {
            react: { version: 'detect' },
        },
        rules: {
            ...reactHooks.configs.recommended.rules,

            'react-compiler/react-compiler': 'off',
            'react-hooks/purity': 'off',
            'react-hooks/preserve-manual-memoization': 'off',
            'react-hooks/set-state-in-effect': 'off',
            'react-hooks/immutability': 'off',
            'react-hooks/refs': 'off',

            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/display-name': 'off',
            'react/no-unescaped-entities': 'warn',

            'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],

            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',

            '@typescript-eslint/no-unused-vars': [
                'warn',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    destructuredArrayIgnorePattern: '^_',
                },
            ],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/no-empty-object-type': 'off',
            '@typescript-eslint/no-require-imports': 'error',
            '@typescript-eslint/consistent-type-imports': [
                'warn',
                { prefer: 'type-imports', fixStyle: 'inline-type-imports' },
            ],

            'no-console': ['warn', { allow: ['warn', 'error'] }],
            'no-debugger': 'warn',
            'prefer-const': 'warn',
            'no-var': 'error',
            eqeqeq: ['error', 'always'],
        },
    },

    {
        files: [
            'resources/ts/**/*.spec.{ts,tsx}',
            'resources/ts/**/*.test.{ts,tsx}',
            'resources/ts/**/*.stories.{ts,tsx}',
            'vitest.setup.ts',
        ],
        rules: {
            '@typescript-eslint/no-explicit-any': 'off',
            'no-console': 'off',
            'react-hooks/rules-of-hooks': 'off',
        },
    },

    {
        files: ['*.config.{js,ts,mjs}', 'vite.config.ts', 'vitest.config.ts'],
        rules: {
            '@typescript-eslint/no-require-imports': 'off',
        },
    },

    prettier,
);
