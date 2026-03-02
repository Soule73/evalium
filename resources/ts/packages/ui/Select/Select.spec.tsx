import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Select from './Select';

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            locale: 'fr',
            language: {
                components: {
                    select: {
                        placeholder: 'Sélectionner',
                        search_placeholder: 'Rechercher...',
                        no_option_found: 'Aucune option trouvée',
                    },
                },
            },
        },
    }),
    router: {
        visit: vi.fn(),
    },
}));

vi.mock('@heroicons/react/24/outline', () => ({
    ChevronDownIcon: ({ className }: { className?: string }) => (
        <div data-testid="chevron-down-icon" className={className} />
    ),
    MagnifyingGlassIcon: ({ className }: { className?: string }) => (
        <div data-testid="search-icon" className={className} />
    ),
    CheckIcon: ({ className }: { className?: string }) => (
        <div data-testid="check-icon" className={className} />
    ),
    Squares2X2Icon: ({ className }: { className?: string }) => (
        <div data-testid="squares-icon" className={className} />
    ),
    CheckCircleIcon: ({ className }: { className?: string }) => (
        <div data-testid="check-circle-icon" className={className} />
    ),
    QuestionMarkCircleIcon: ({ className }: { className?: string }) => (
        <div data-testid="question-mark-circle-icon" className={className} />
    ),
    PencilIcon: ({ className }: { className?: string }) => (
        <div data-testid="pencil-icon" className={className} />
    ),
}));

const mockOptions = [
    { value: '1', label: 'Option 1' },
    { value: '2', label: 'Option 2' },
    { value: '3', label: 'Option 3' },
];

describe('Select Component', () => {
    const defaultProps = {
        options: mockOptions,
        placeholder: 'Sélectionner une option',
        onChange: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders with placeholder when no value is selected', () => {
        render(<Select {...defaultProps} />);

        expect(screen.getByText('Sélectionner une option')).toBeInTheDocument();
        expect(screen.getByTestId('chevron-down-icon')).toBeInTheDocument();
    });

    it('displays selected value when provided', () => {
        render(<Select {...defaultProps} value="1" />);

        expect(screen.getByText('Option 1')).toBeInTheDocument();
    });

    it('opens dropdown when clicked', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} />);

        const trigger = screen.getByText('Sélectionner une option');
        await user.click(trigger);

        await waitFor(() => {
            expect(screen.getByText('Option 1')).toBeVisible();
            expect(screen.getByText('Option 2')).toBeVisible();
            expect(screen.getByText('Option 3')).toBeVisible();
        });
    });

    it('calls onChange when option is selected', async () => {
        const user = userEvent.setup();
        const mockOnChange = vi.fn();

        render(<Select {...defaultProps} onChange={mockOnChange} />);

        const trigger = screen.getByText('Sélectionner une option');
        await user.click(trigger);

        await waitFor(() => screen.getByText('Option 2'));
        await user.click(screen.getByText('Option 2'));

        expect(mockOnChange).toHaveBeenCalledWith('2');
    });

    it('filters options when searchable', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} searchable />);

        const trigger = screen.getByText('Sélectionner une option');
        await user.click(trigger);

        await waitFor(() => screen.getByTestId('search-icon'));

        const searchInput = screen.getByRole('textbox');
        await user.type(searchInput, 'Option 2');

        await waitFor(() => {
            expect(screen.getByText('Option 2')).toBeVisible();
            expect(screen.queryByText('Option 1')).not.toBeInTheDocument();
        });
    });

    it('renders with error state', () => {
        render(<Select {...defaultProps} error="This field is required" />);

        expect(screen.getByText('This field is required')).toBeInTheDocument();
    });

    it('displays label when provided', () => {
        render(<Select {...defaultProps} label="Select Label" />);

        expect(screen.getByText('Select Label')).toBeInTheDocument();
    });
});
