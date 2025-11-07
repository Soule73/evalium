import '@testing-library/jest-dom';
import React from 'react';

// Extend global types
declare global {
    var route: jest.Mock;
}

// Mock pour Inertia.js usePage
jest.mock('@inertiajs/react', () => ({
    ...jest.requireActual('@inertiajs/react'),
    usePage: jest.fn().mockReturnValue({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                    roles: [],
                },
                permissions: [],
            },
            locale: 'fr',
            language: {
                common: {
                    cancel: 'Annuler',
                    confirm: 'Confirmer',
                    delete: 'Supprimer',
                    save: 'Enregistrer',
                },
                components: {
                    confirmation_modal: {
                        confirm: 'Confirmer',
                        cancel: 'Annuler',
                    },
                    select: {
                        placeholder: 'Sélectionner une option',
                        search_placeholder: 'Rechercher...',
                        no_option_found: 'Aucune option trouvée',
                    },
                },
            },
        },
        url: '/test',
        component: 'Test',
        version: '1',
        errors: {},
        rememberedState: {},
        resolvedErrors: {},
        scrollRegions: [],
        clearHistory: jest.fn(),
        setScrollRegions: jest.fn(),
    }),
    router: {
        visit: jest.fn(),
        get: jest.fn(),
        post: jest.fn(),
        put: jest.fn(),
        patch: jest.fn(),
        delete: jest.fn(),
        reload: jest.fn(),
        on: jest.fn(),
    },
}));

// Mock pour route helper
(global as any).route = jest.fn().mockImplementation((name?: string, params?: Record<string, any>) => {
    if (params) {
        return `/${name}/${Object.values(params).join('/')}`;
    }
    return `/${name}`;
});

// Mock pour les assets Vite
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: jest.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(), // deprecated
        removeListener: jest.fn(), // deprecated
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    })),
});

// Mock pour ResizeObserver
global.ResizeObserver = jest.fn().mockImplementation(() => ({
    observe: jest.fn(),
    unobserve: jest.fn(),
    disconnect: jest.fn(),
})) as any;

// Mock pour scrollIntoView
Element.prototype.scrollIntoView = jest.fn();

// Mock MarkdownRenderer pour éviter problèmes ESM
jest.mock('@/Components/forms/MarkdownRenderer', () => ({
    __esModule: true,
    default: ({ children }: { children: string }) => React.createElement('div', null, children),
    MarkdownRenderer: ({ children }: { children: string }) => React.createElement('div', null, children),
}));

// Configuration par défaut pour les tests
beforeEach(() => {
    jest.clearAllMocks();
});
