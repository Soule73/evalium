import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import ConfirmationModal from './ConfirmationModal';

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            locale: 'fr',
            language: {
                components: {
                    confirmation_modal: {
                        confirm: 'Confirmer',
                        cancel: 'Annuler',
                    },
                },
            },
        },
    }),
    router: {
        visit: vi.fn(),
    },
}));

describe('ConfirmationModal Component', () => {
    const defaultProps = {
        isOpen: true,
        onClose: vi.fn(),
        onConfirm: vi.fn(),
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders with default props', () => {
        render(<ConfirmationModal {...defaultProps} confirmText="Confirm" cancelText="Cancel" />);

        expect(screen.getByText('Confirm Action')).toBeInTheDocument();
        expect(screen.getByText('Are you sure you want to proceed?')).toBeInTheDocument();
        expect(screen.getByText('Confirm')).toBeInTheDocument();
        expect(screen.getByText('Cancel')).toBeInTheDocument();
    });

    it('does not render when isOpen is false', () => {
        render(
            <ConfirmationModal
                {...defaultProps}
                isOpen={false}
                confirmText="Confirm"
                cancelText="Cancel"
            />,
        );

        expect(screen.queryByText('Confirm Action')).not.toBeInTheDocument();
    });

    it('calls onConfirm when confirm button is clicked', async () => {
        const user = userEvent.setup();
        const mockOnConfirm = vi.fn();

        render(
            <ConfirmationModal
                {...defaultProps}
                onConfirm={mockOnConfirm}
                confirmText="Confirm"
                cancelText="Cancel"
            />,
        );

        const confirmButton = screen.getByText('Confirm');
        await user.click(confirmButton);

        expect(mockOnConfirm).toHaveBeenCalledTimes(1);
    });

    it('calls onClose when cancel button is clicked', async () => {
        const user = userEvent.setup();
        const mockOnClose = vi.fn();

        render(
            <ConfirmationModal
                {...defaultProps}
                onClose={mockOnClose}
                confirmText="Confirm"
                cancelText="Cancel"
            />,
        );

        const cancelButton = screen.getByText('Cancel');
        await user.click(cancelButton);

        expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('renders with custom labels', () => {
        render(
            <ConfirmationModal {...defaultProps} confirmText="Yes, delete" cancelText="No, keep" />,
        );

        expect(screen.getByText('Yes, delete')).toBeInTheDocument();
        expect(screen.getByText('No, keep')).toBeInTheDocument();
    });

    it('renders with danger type', () => {
        render(
            <ConfirmationModal
                {...defaultProps}
                type="danger"
                confirmText="Confirm"
                cancelText="Cancel"
            />,
        );

        const confirmButton = screen.getByText('Confirm');
        expect(confirmButton).toHaveClass('bg-red-600');
    });

    it('renders with warning type', () => {
        render(
            <ConfirmationModal
                {...defaultProps}
                type="warning"
                confirmText="Confirm"
                cancelText="Cancel"
            />,
        );

        const confirmButton = screen.getByText('Confirm');
        expect(confirmButton).toHaveClass('bg-yellow-600');
    });

    it('disables buttons when loading', () => {
        render(
            <ConfirmationModal
                {...defaultProps}
                loading
                confirmText="Confirm"
                cancelText="Cancel"
            />,
        );

        const confirmButton = screen.getByText('Confirm');
        const cancelButton = screen.getByText('Cancel');

        expect(confirmButton).toBeDisabled();
        expect(cancelButton).toBeDisabled();
    });

    it('renders children when provided', () => {
        render(
            <ConfirmationModal {...defaultProps} confirmText="Confirm" cancelText="Cancel">
                <div>Custom content</div>
            </ConfirmationModal>,
        );

        expect(screen.getByText('Custom content')).toBeInTheDocument();
    });
});
