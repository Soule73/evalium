import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { Select } from '@/Components';

// Mock pour les icônes Heroicons
jest.mock('@heroicons/react/24/outline', () => ({
    ChevronDownIcon: ({ className }: { className?: string }) => (
        <div data-testid="chevron-down-icon" className={className} />
    ),
    MagnifyingGlassIcon: ({ className }: { className?: string }) => (
        <div data-testid="search-icon" className={className} />
    ),
    CheckIcon: ({ className }: { className?: string }) => (
        <div data-testid="check-icon" className={className} />
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
        onChange: jest.fn(),
    };

    beforeEach(() => {
        jest.clearAllMocks();
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

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        expect(screen.getByText('Option 1')).toBeInTheDocument();
        expect(screen.getByText('Option 2')).toBeInTheDocument();
        expect(screen.getByText('Option 3')).toBeInTheDocument();
    });

    it('calls onChange when option is selected', async () => {
        const user = userEvent.setup();
        const mockOnChange = jest.fn();
        render(<Select {...defaultProps} onChange={mockOnChange} />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        const option = screen.getByText('Option 2');
        await user.click(option);

        expect(mockOnChange).toHaveBeenCalledWith('2');
    });

    it('shows search input when searchable is true', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} searchable />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        expect(screen.getByPlaceholderText('Rechercher...')).toBeInTheDocument();
        expect(screen.getByTestId('search-icon')).toBeInTheDocument();
    });

    it('filters options when searching', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} searchable />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        const searchInput = screen.getByPlaceholderText('Rechercher...');
        await user.type(searchInput, 'Option 1');

        expect(screen.getByText('Option 1')).toBeInTheDocument();
        expect(screen.queryByText('Option 2')).not.toBeInTheDocument();
        expect(screen.queryByText('Option 3')).not.toBeInTheDocument();
    });

    it('shows "Aucune option trouvée" when no results match search', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} searchable />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        const searchInput = screen.getByPlaceholderText('Rechercher...');
        await user.type(searchInput, 'Inexistant');

        expect(screen.getByText('Aucune option trouvée')).toBeInTheDocument();
    });

    it('closes dropdown when clicking outside', async () => {
        const user = userEvent.setup();
        render(
            <div>
                <Select {...defaultProps} />
                <div data-testid="outside">Outside element</div>
            </div>
        );

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        expect(screen.getByText('Option 1')).toBeInTheDocument();

        const outside = screen.getByTestId('outside');
        await user.click(outside);

        await waitFor(() => {
            expect(screen.queryByText('Option 1')).not.toBeInTheDocument();
        });
    });

    it('navigates options with keyboard', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        // Naviguer vers le bas
        await user.keyboard('[ArrowDown]');
        // Note: La navigation clavier peut ne pas être implémentée dans le composant
        // On peut simplement vérifier que les options sont visibles
        expect(screen.getByText('Option 1')).toBeInTheDocument();

        await user.keyboard('[ArrowDown]');
        expect(screen.getByText('Option 2')).toBeInTheDocument();

        // Naviguer vers le haut
        await user.keyboard('[ArrowUp]');
        expect(screen.getByText('Option 1')).toBeInTheDocument();

        // Sélectionner avec Enter
        await user.keyboard('[Enter]');
        expect(defaultProps.onChange).toHaveBeenCalledWith('1');
    });

    it('escapes to close dropdown', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        expect(screen.getByText('Option 1')).toBeInTheDocument();

        await user.keyboard('[Escape]');

        await waitFor(() => {
            expect(screen.queryByText('Option 1')).not.toBeInTheDocument();
        });
    });

    it('displays error message when error prop is provided', () => {
        render(<Select {...defaultProps} error="Ce champ est requis" />);

        expect(screen.getByText('Ce champ est requis')).toBeInTheDocument();
        expect(screen.getByRole('combobox')).toHaveClass('border-red-500');
    });

    it('is disabled when disabled prop is true', () => {
        render(<Select {...defaultProps} disabled />);

        const combobox = screen.getByRole('combobox');
        // Un div avec role combobox n'est pas "disabled" comme un input, on vérifie les classes CSS
        expect(combobox).toHaveClass('opacity-60', 'cursor-not-allowed');
    });

    it('shows check icon for selected option in dropdown', async () => {
        const user = userEvent.setup();
        render(<Select {...defaultProps} value="2" />);

        const combobox = screen.getByRole('combobox');
        await user.click(combobox);

        // Vérifier que l'icône de check existe pour l'option sélectionnée
        expect(screen.getByTestId('check-icon')).toBeInTheDocument();
    });
});
