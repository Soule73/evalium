import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import Button from './Button';

describe('Button Component', () => {
    it('renders with default props', () => {
        render(<Button>Click me</Button>);

        const button = screen.getByRole('button', { name: /click me/i });
        expect(button).toBeInTheDocument();
        expect(button).toHaveClass('bg-indigo-600', 'text-white');
    });

    it('renders with different colors', () => {
        const { rerender } = render(<Button color="secondary">Secondary</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-gray-600');

        rerender(<Button color="success">Success</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-green-600');

        rerender(<Button color="danger">Danger</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-red-600');
    });

    it('renders with different variants', () => {
        const { rerender } = render(<Button variant="outline">Outline</Button>);
        expect(screen.getByRole('button')).toHaveClass('border-indigo-600', 'text-indigo-600');

        rerender(<Button variant="ghost">Ghost</Button>);
        expect(screen.getByRole('button')).toHaveClass('text-indigo-600');
    });

    it('renders with different sizes', () => {
        const { rerender } = render(<Button size="sm">Small</Button>);
        expect(screen.getByRole('button')).toHaveClass('px-2', 'py-1', 'text-sm');

        rerender(<Button size="lg">Large</Button>);
        expect(screen.getByRole('button')).toHaveClass('px-6', 'py-3', 'text-lg');
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const mockOnClick = vi.fn();

        render(<Button onClick={mockOnClick}>Click me</Button>);

        const button = screen.getByRole('button', { name: /click me/i });
        await user.click(button);

        expect(mockOnClick).toHaveBeenCalledTimes(1);
    });

    it('is disabled when disabled prop is true', () => {
        render(<Button disabled>Disabled</Button>);

        const button = screen.getByRole('button', { name: /disabled/i });
        expect(button).toBeDisabled();
        expect(button).toHaveClass('disabled:opacity-50', 'disabled:cursor-not-allowed');
    });

    it('shows loading state', () => {
        render(<Button loading>Loading</Button>);

        const button = screen.getByRole('button');
        expect(button).toBeDisabled();
        expect(screen.getByText('Loading')).toBeInTheDocument();
    });

    it('forwards additional props', () => {
        render(
            <Button data-testid="custom-button" type="submit">
                Submit
            </Button>,
        );

        const button = screen.getByRole('button');
        expect(button).toHaveAttribute('data-testid', 'custom-button');
        expect(button).toHaveAttribute('type', 'submit');
    });

    it('forwards additional props', () => {
        render(
            <Button data-testid="custom-button" type="submit">
                Submit
            </Button>,
        );

        const button = screen.getByTestId('custom-button');
        expect(button).toHaveAttribute('type', 'submit');
    });
});
