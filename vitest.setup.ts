import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';

afterEach(() => {
    cleanup();
});

// Mock scrollIntoView which is not implemented in jsdom
Element.prototype.scrollIntoView = () => {};
