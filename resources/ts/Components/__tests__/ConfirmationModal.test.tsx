import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import ConfirmationModal from '../ConfirmationModal';

describe('ConfirmationModal Component', () => {
    const defaultProps = {
        isOpen: true,
        onClose: jest.fn(),
        onConfirm: jest.fn(),
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders with default props', () => {
        render(<ConfirmationModal {...defaultProps} />);

        expect(screen.getByText('Confirm Action')).toBeInTheDocument();
        expect(screen.getByText('Are you sure you want to proceed?')).toBeInTheDocument();
        expect(screen.getByText('Confirmer')).toBeInTheDocument();
        expect(screen.getByText('Annuler')).toBeInTheDocument();
    });

    it('does not render when isOpen is false', () => {
        render(<ConfirmationModal {...defaultProps} isOpen={false} />);

        expect(screen.queryByText('Confirm Action')).not.toBeInTheDocument();
    });

    it('calls onConfirm when confirm button is clicked', async () => {
        const user = userEvent.setup();
        const mockOnConfirm = jest.fn();

        render(<ConfirmationModal {...defaultProps} onConfirm={mockOnConfirm} />);

        const confirmButton = screen.getByText('Confirmer');
        await user.click(confirmButton);

        expect(mockOnConfirm).toHaveBeenCalledTimes(1);
    });

    it('calls onClose when cancel button is clicked', async () => {
        const user = userEvent.setup();
        const mockOnClose = jest.fn();

        render(<ConfirmationModal {...defaultProps} onClose={mockOnClose} />);

        const cancelButton = screen.getByText('Annuler');
        await user.click(cancelButton);

        expect(mockOnClose).toHaveBeenCalledTimes(1);
    });

    it('renders with custom button text', () => {
        render(
            <ConfirmationModal
                {...defaultProps}
                confirmText="Yes, proceed"
                cancelText="No, cancel"
            />
        );

        expect(screen.getByText('Yes, proceed')).toBeInTheDocument();
        expect(screen.getByText('No, cancel')).toBeInTheDocument();
    });

    it('renders with danger type styling', () => {
        const { container } = render(
            <ConfirmationModal {...defaultProps} type="danger" />
        );

        const icon = container.querySelector('.text-red-600');
        expect(icon).toBeInTheDocument();
    });

    it('renders with warning type styling', () => {
        const { container } = render(
            <ConfirmationModal {...defaultProps} type="warning" />
        );

        const icon = container.querySelector('.text-yellow-600');
        expect(icon).toBeInTheDocument();
    });

    it('renders with info type styling', () => {
        const { container } = render(
            <ConfirmationModal {...defaultProps} type="info" />
        );

        const icon = container.querySelector('.text-blue-600');
        expect(icon).toBeInTheDocument();
    });

    it('disables buttons when loading', () => {
        const mockOnClose = jest.fn();

        render(
            <ConfirmationModal
                {...defaultProps}
                onClose={mockOnClose}
                loading={true}
                isCloseableInside={false}
            />
        );

        const confirmButton = screen.getByText('Confirmer');
        const cancelButton = screen.getByText('Annuler');

        expect(confirmButton).toBeDisabled();
        expect(cancelButton).toBeDisabled();
    });

    it('renders children content', () => {
        render(
            <ConfirmationModal {...defaultProps}>
                <div>Additional content</div>
            </ConfirmationModal>
        );

        expect(screen.getByText('Additional content')).toBeInTheDocument();
    });

    it('renders with different modal sizes', () => {
        const { rerender } = render(
            <ConfirmationModal {...defaultProps} size="sm" />
        );

        expect(screen.getByText('Confirm Action')).toBeInTheDocument();

        rerender(<ConfirmationModal {...defaultProps} size="lg" />);

        expect(screen.getByText('Confirm Action')).toBeInTheDocument();
    });
});
