/** @type {import('jest').Config} */
module.exports = {
    // Environnement de test
    testEnvironment: 'jsdom',

    // Extensions de fichiers supportées
    moduleFileExtensions: ['js', 'jsx', 'ts', 'tsx', 'json'],

    // Transformation des fichiers
    transform: {
        '^.+\\.(ts|tsx)$': ['ts-jest', {
            tsconfig: {
                jsx: 'react-jsx',
            }
        }],
        '^.+\\.(js|jsx)$': 'babel-jest',
    },

    // Fichiers de setup
    setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],

    // Pattern des fichiers de test
    testMatch: [
        '<rootDir>/resources/ts/**/__tests__/**/*.{js,jsx,ts,tsx}',
        '<rootDir>/resources/ts/**/*.{test,spec}.{js,jsx,ts,tsx}',
        '<rootDir>/tests/frontend/**/*.{test,spec}.{js,jsx,ts,tsx}',
    ],

    // Fichiers à ignorer
    testPathIgnorePatterns: [
        '<rootDir>/node_modules/',
        '<rootDir>/vendor/',
        '<rootDir>/storage/',
        '<rootDir>/bootstrap/cache/',
    ],

    // Configuration de la couverture de code
    collectCoverageFrom: [
        'resources/ts/**/*.{js,jsx,ts,tsx}',
        '!resources/ts/**/*.d.ts',
        '!resources/ts/app.tsx',
        '!resources/ts/bootstrap.ts',
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