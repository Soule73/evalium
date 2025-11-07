import type { Config } from 'jest';

const config: Config = {
    // Environnement de test
    testEnvironment: 'jsdom',

    // Extensions de fichiers supportées
    moduleFileExtensions: ['js', 'jsx', 'ts', 'tsx', 'json'],

    // Transformation des fichiers
    transform: {
        '^.+\\.(ts|tsx)$': ['ts-jest', {
            tsconfig: '<rootDir>/tsconfig.test.json',
        }],
        '^.+\\.(js|jsx)$': 'babel-jest',
    },

    // Fichiers de setup
    setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],

    // Pattern des fichiers de test
    testMatch: [
        '<rootDir>/resources/ts/tests/unit/**/*.{test,spec}.{js,jsx,ts,tsx}',
        '<rootDir>/tests/frontend/**/*.{test,spec}.{js,jsx,ts,tsx}',
    ],

    // Fichiers à ignorer
    testPathIgnorePatterns: [
        '<rootDir>/node_modules/',
        '<rootDir>/vendor/',
        '<rootDir>/storage/',
        '<rootDir>/bootstrap/cache/',
        '<rootDir>/resources/ts/tests/e2e/',
    ],

    // Transformer les modules ESM de node_modules
    transformIgnorePatterns: [
        'node_modules/(?!(lodash-es|@inertiajs|react-markdown|remark-.*|rehype-.*|unified|bail|is-plain-obj|trough|vfile|vfile-message|unist-.*|micromark.*|decode-named-character-reference|character-entities|mdast-util-.*|ccount|escape-string-regexp|markdown-table|devlop|hast-.*|hastscript|property-information|space-separated-tokens|comma-separated-tokens|web-namespaces|ziggy-js|qs-esm)/)',
    ],

    // Configuration de la couverture de code
    collectCoverageFrom: [
        'resources/ts/**/*.{js,jsx,ts,tsx}',
        '!resources/ts/**/*.d.ts',
        '!resources/ts/app.tsx',
        '!resources/ts/bootstrap.ts',
        '!resources/ts/tests/**',
    ],

    // Seuils de couverture
    coverageThreshold: {
        global: {
            branches: 70,
            functions: 70,
            lines: 70,
            statements: 70,
        },
    },

    // Configuration pour les modules
    moduleNameMapper: {
        '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
        '\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$': 'jest-transform-stub',
        '^@/(.*)$': '<rootDir>/resources/ts/$1',
        '^ziggy-js$': '<rootDir>/node_modules/ziggy-js/dist/index.js',
    },

    // Configuration pour les tests asynchrones
    testTimeout: 10000,
};

export default config;
