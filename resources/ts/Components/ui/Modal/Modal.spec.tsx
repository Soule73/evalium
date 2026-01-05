import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';
import Modal from './Modal';

describe('Modal Component', () => {
    it('renders when isOpen is true', () => {
        render(
            <Modal isOpen={true} onClose={() => { }}>
                <div>Modal Content</div>
            </Modal>
        );

        expect(screen.getByText('Modal Content')).toBeInTheDocument();
    });

    it('does not render when isOpen is false', () => {
        render(
            <Modal isOpen={false} onClose={() => { }}>
                <div>Modal Content</div>
            </Modal>
        );

        expect(screen.queryByText('Modal Content')).not.toBeInTheDocument();
    });

    it('calls onClose when backdrop is clicked', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        const { container } = render(
            <Modal isOpen={true} onClose={mockOnClose}>
                <div>Modal Content</div>
            </Modal>
        );

        const backdrop = container.querySelector('.bg-black');
        await user.click(backdrop!);

        expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('calls onClose when clicking backdrop', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        const { container } = render(
            <Modal isOpen={true} onClose={mockOnClose} isCloseableInside={true}>
                <div>Modal Content</div>
            </Modal>
        );

        const backdrop = container.querySelector('.bg-black');
        if (backdrop) {
            await user.click(backdrop);
            expect(mockOnClose).toHaveBeenCalled();
        }
    });

    it('does not close when clicking inside modal', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        render(
            <Modal isOpen={true} onClose={mockOnClose}>
                <div>Modal Content</div>
            </Modal>
        );

        const modalContent = screen.getByText('Modal Content');
        await user.click(modalContent);

        expect(mockOnClose).not.toHaveBeenCalled();
    });

    it('renders with different sizes', () => {
        const { rerender, container } = render(
            <Modal isOpen={true} onClose={() => { }} size="sm">
                <div>Small Modal</div>
            </Modal>
        );

        let dialog = container.querySelector('.max-w-sm');
        expect(dialog).toBeInTheDocument();

        rerender(
            <Modal isOpen={true} onClose={() => { }} size="lg">
                <div>Large Modal</div>
            </Modal>
        );

        dialog = container.querySelector('.max-w-lg');
        expect(dialog).toBeInTheDocument();
    });

    it('hides close button when isCloseableInside is false', () => {
        render(
            <Modal isOpen={true} onClose={() => { }} isCloseableInside={false}>
                <div>Modal Content</div>
            </Modal>
        );

        const closeButton = screen.queryByRole('button');
        expect(closeButton).not.toBeInTheDocument();
    });
});
