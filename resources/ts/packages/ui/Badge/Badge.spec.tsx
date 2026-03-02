import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import Badge from './Badge';

describe('Badge Component', () => {
    it('renders with label text', () => {
        render(<Badge label="Test Badge" type="info" />);
        expect(screen.getByText('Test Badge')).toBeInTheDocument();
    });

    it('renders with success type', () => {
        const { container } = render(<Badge label="Success" type="success" />);
        const badge = container.firstChild;
        expect(badge).toHaveClass('text-green-600', 'bg-green-600/10');
    });

    it('renders with error type', () => {
        const { container } = render(<Badge label="Error" type="error" />);
        expect(container.firstChild).toHaveClass('text-red-600', 'bg-red-600/10');
    });

    it('renders with warning type', () => {
        const { container } = render(<Badge label="Warning" type="warning" />);
        expect(container.firstChild).toHaveClass('text-yellow-600', 'bg-yellow-600/10');
    });

    it('renders with info type', () => {
        const { container } = render(<Badge label="Info" type="info" />);
        expect(container.firstChild).toHaveClass('text-blue-600', 'bg-blue-600/10');
    });

    it('renders with gray type', () => {
        const { container } = render(<Badge label="Gray" type="gray" />);
        expect(container.firstChild).toHaveClass('text-gray-600', 'bg-gray-600/10');
    });

    it('applies correct base styles', () => {
        const { container } = render(<Badge label="Badge" type="info" />);
        expect(container.firstChild).toHaveClass(
            'w-max',
            'font-medium',
            'rounded-lg',
            'text-sm',
            'px-3',
            'py-1.5',
        );
    });

    it('applies correct sm size styles', () => {
        const { container } = render(<Badge label="Badge" type="info" size="sm" />);
        expect(container.firstChild).toHaveClass(
            'w-max',
            'font-medium',
            'rounded-lg',
            'text-xs',
            'px-1.5',
            'py-0.5',
        );
    });
});
