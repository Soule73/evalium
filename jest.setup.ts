import '@testing-library/jest-dom';

// Extend global types
declare global {
    var route: jest.Mock;
}

// Mock pour Inertia.js
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

// Configuration par défaut pour les tests
beforeEach(() => {
    jest.clearAllMocks();
});
