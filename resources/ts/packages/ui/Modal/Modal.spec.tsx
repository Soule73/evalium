import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import Modal from './Modal';

describe('Modal Component', () => {
    it('renders when isOpen is true', async () => {
        render(
            <Modal isOpen={true} onClose={() => {}}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(screen.getByText('Modal Content')).toBeInTheDocument();
        });
    });

    it('does not render when isOpen is false', () => {
        render(
            <Modal isOpen={false} onClose={() => {}}>
                <div>Modal Content</div>
            </Modal>,
        );

        expect(screen.queryByText('Modal Content')).not.toBeInTheDocument();
    });

    it('calls onClose when backdrop is clicked', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        render(
            <Modal isOpen={true} onClose={mockOnClose}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(screen.getByText('Modal Content')).toBeInTheDocument();
        });

        const backdrop = document.querySelector('[aria-hidden="true"]');
        await user.click(backdrop!);

        expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('calls onClose when pressing Escape', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        render(
            <Modal isOpen={true} onClose={mockOnClose} isCloseableInside={true}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(screen.getByText('Modal Content')).toBeInTheDocument();
        });

        await user.keyboard('{Escape}');

        expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('does not call onClose on Escape when isCloseableInside is false', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        render(
            <Modal isOpen={true} onClose={mockOnClose} isCloseableInside={false}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(screen.getByText('Modal Content')).toBeInTheDocument();
        });

        await user.keyboard('{Escape}');

        expect(mockOnClose).not.toHaveBeenCalled();
    });

    it('does not close when clicking inside modal', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        render(
            <Modal isOpen={true} onClose={mockOnClose}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(screen.getByText('Modal Content')).toBeInTheDocument();
        });

        const modalContent = screen.getByText('Modal Content');
        await user.click(modalContent);

        expect(mockOnClose).not.toHaveBeenCalled();
    });

    it('renders with different sizes', async () => {
        const { rerender } = render(
            <Modal isOpen={true} onClose={() => {}} size="sm">
                <div>Small Modal</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(document.querySelector('.sm\\:max-w-sm')).toBeInTheDocument();
        });

        rerender(
            <Modal isOpen={true} onClose={() => {}} size="lg">
                <div>Large Modal</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(document.querySelector('.sm\\:max-w-lg')).toBeInTheDocument();
        });
    });

    it('hides close button when isCloseableInside is false', async () => {
        render(
            <Modal isOpen={true} onClose={() => {}} isCloseableInside={false}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(screen.getByText('Modal Content')).toBeInTheDocument();
        });

        const closeButton = screen.queryByLabelText('Close');
        expect(closeButton).not.toBeInTheDocument();
    });

    it('locks body scroll when open', async () => {
        const { unmount } = render(
            <Modal isOpen={true} onClose={() => {}}>
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            expect(document.body.style.overflow).toBe('hidden');
        });

        unmount();

        expect(document.body.style.overflow).not.toBe('hidden');
    });

    it('renders with aria attributes for accessibility', async () => {
        render(
            <Modal isOpen={true} onClose={() => {}} title="Test Title">
                <div>Modal Content</div>
            </Modal>,
        );

        await waitFor(() => {
            const dialog = screen.getByRole('dialog');
            expect(dialog).toHaveAttribute('aria-modal', 'true');
            expect(dialog).toHaveAttribute('aria-labelledby', 'modal-title');
        });
    });
});
