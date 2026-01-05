import { useState, useRef, useEffect, forwardRef } from 'react';
import { ChevronDownIcon, CheckIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';

interface Option {
    value: string | number;
    label: string;
    disabled?: boolean;
}

interface SelectProps {
    label?: string;
    error?: string;
    helperText?: string;
    options: Option[];
    placeholder?: string;
    noOptionFound?: string;
    searchPlaceholder?: string;
    value?: string | number;
    onChange?: (value: string | number) => void;
    onBlur?: () => void;
    disabled?: boolean;
    searchable?: boolean;
    className?: string;
    name?: string;
}

const Select = forwardRef<HTMLDivElement, SelectProps>(
    ({
        label,
        error,
        helperText,
        options,
        placeholder,
        noOptionFound,
        searchPlaceholder,
        value,
        onChange,
        onBlur,
        disabled = false,
        searchable = true,
        className = '',
        name
    }, ref) => {

        const [isOpen, setIsOpen] = useState(false);
        const [searchTerm, setSearchTerm] = useState('');
        const [filteredOptions, setFilteredOptions] = useState(options);
        const [highlightedIndex, setHighlightedIndex] = useState(-1);

        const dropdownRef = useRef<HTMLDivElement>(null);
        const searchInputRef = useRef<HTMLInputElement>(null);
        const listRef = useRef<HTMLUListElement>(null);

        // Filtrer les options basées sur le terme de recherche
        useEffect(() => {
            if (!searchable) {
                setFilteredOptions(options);
                return;
            }

            const filtered = options.filter(option =>
                option.label.toLowerCase().includes(searchTerm.toLowerCase())
            );
            setFilteredOptions(filtered);
            setHighlightedIndex(-1);
        }, [searchTerm, options, searchable]);

        // Fermer le dropdown quand on clique à l'extérieur
        useEffect(() => {
            const handleClickOutside = (event: MouseEvent) => {
                if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                    setIsOpen(false);
                    setSearchTerm('');
                    onBlur?.();
                }
            };

            document.addEventListener('mousedown', handleClickOutside);
            return () => document.removeEventListener('mousedown', handleClickOutside);
        }, [onBlur]);

        // Focus sur le champ de recherche quand le dropdown s'ouvre
        useEffect(() => {
            if (isOpen && searchable && searchInputRef.current) {
                searchInputRef.current.focus();
            }
        }, [isOpen, searchable]);

        // Gestion des touches du clavier
        const handleKeyDown = (e: React.KeyboardEvent) => {
            if (!isOpen) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    setIsOpen(true);
                }
                return;
            }

            switch (e.key) {
                case 'Escape':
                    setIsOpen(false);
                    setSearchTerm('');
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    setHighlightedIndex(prev =>
                        prev < filteredOptions.length - 1 ? prev + 1 : prev
                    );
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    setHighlightedIndex(prev => (prev > 0 ? prev - 1 : prev));
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (highlightedIndex >= 0 && filteredOptions[highlightedIndex]) {
                        handleSelect(filteredOptions[highlightedIndex]);
                    }
                    break;
            }
        };

        // Faire défiler l'option en surbrillance dans la vue
        useEffect(() => {
            if (highlightedIndex >= 0 && listRef.current) {
                const highlightedElement = listRef.current.children[highlightedIndex] as HTMLElement;
                if (highlightedElement) {
                    highlightedElement.scrollIntoView({
                        block: 'nearest',
                        behavior: 'smooth'
                    });
                }
            }
        }, [highlightedIndex]);

        const handleSelect = (option: Option) => {
            if (option.disabled) return;

            onChange?.(option.value);
            setIsOpen(false);
            setSearchTerm('');
            setHighlightedIndex(-1);
        };

        const handleToggle = () => {
            if (disabled) return;
            setIsOpen(!isOpen);
            if (!isOpen) {
                setSearchTerm('');
            }
        };

        const selectedOption = options.find(opt => opt.value === value);

        const baseClasses = 'relative w-full';
        const triggerClasses = `
            w-full px-3 py-2 bg-white dark:bg-[--color-dark-surface] border rounded-md
            focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-[--color-dark-primary]
            transition-all duration-200 cursor-pointer
            flex items-center justify-between
            ${error
                ? 'border-red-500 dark:border-[--color-dark-danger] focus:border-red-500 dark:focus:border-[--color-dark-danger] focus:ring-red-500 dark:focus:ring-[--color-dark-danger]'
                : 'border-gray-300 dark:border-[--color-dark-border] focus:border-blue-500 dark:focus:border-[--color-dark-primary] hover:border-gray-400 dark:hover:border-[--color-dark-text-secondary]'
            }
            ${disabled ? 'bg-gray-50 dark:bg-[--color-dark-surface] cursor-not-allowed opacity-60' : ''}
            ${isOpen ? 'ring-2 ring-blue-500 dark:ring-[--color-dark-primary] border-blue-500 dark:border-[--color-dark-primary]' : ''}
        `.trim();

        return (
            <div className={`${baseClasses} ${className}`} ref={ref}>
                {/* Hidden input for form submission */}
                <input type="hidden" name={name} value={value || ''} />

                {label && (
                    <label className="block text-sm font-medium text-gray-700 dark:text-[--color-dark-text] mb-1">
                        {label}
                    </label>
                )}

                <div className="relative" ref={dropdownRef}>
                    {/* Trigger Button */}
                    <div
                        className={triggerClasses}
                        onClick={handleToggle}
                        onKeyDown={handleKeyDown}
                        tabIndex={disabled ? -1 : 0}
                        role="combobox"
                        aria-expanded={isOpen}
                        aria-haspopup="listbox"
                        aria-label={label || 'Select option'}
                    >
                        <span className={`block truncate ${!selectedOption ? 'text-gray-500 dark:text-[--color-dark-text-muted]' : 'text-gray-900 dark:text-[--color-dark-text]'}`}>
                            {selectedOption ? selectedOption.label : (placeholder || '')}
                        </span>
                        <ChevronDownIcon
                            className={`h-5 w-5 text-gray-400 dark:text-[--color-dark-text-secondary] transition-transform duration-200 ${isOpen ? 'rotate-180' : ''
                                }`}
                        />
                    </div>

                    {/* Dropdown */}
                    {isOpen && (
                        <div className="absolute z-50 w-full mt-1 bg-white dark:bg-[--color-dark-surface] border border-gray-300 dark:border-[--color-dark-border] rounded-md shadow-lg dark:shadow-2xl">
                            {/* Search Input */}
                            {searchable && (
                                <div className="p-2 border-b border-gray-200 dark:border-[--color-dark-border]">
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-[--color-dark-text-secondary]" />
                                        <input
                                            ref={searchInputRef}
                                            type="text"
                                            className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-[--color-dark-border] rounded focus:outline-none focus:ring-1 focus:ring-blue-500 dark:focus:ring-[--color-dark-primary] focus:border-blue-500 dark:focus:border-[--color-dark-primary] bg-white dark:bg-[--color-dark-bg] text-gray-900 dark:text-[--color-dark-text]"
                                            placeholder={searchPlaceholder}
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyDown={handleKeyDown}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Options List */}
                            <ul
                                ref={listRef}
                                className="max-h-60 overflow-auto custom-scrollbar py-1"
                                role="listbox"
                            >
                                {filteredOptions.length === 0 ? (
                                    <li className="px-3 py-2 text-sm text-gray-500 dark:text-[--color-dark-text-muted] text-center">
                                        {noOptionFound}
                                    </li>
                                ) : (
                                    filteredOptions.map((option, index) => (
                                        <li
                                            key={option.value}
                                            className={`
                                                px-3 py-2 text-sm cursor-pointer flex items-center justify-between
                                                transition-colors duration-150
                                                ${option.disabled
                                                    ? 'text-gray-400 dark:text-[--color-dark-text-muted] cursor-not-allowed'
                                                    : index === highlightedIndex
                                                        ? 'bg-blue-50 dark:bg-[--color-dark-primary]/20 text-blue-900 dark:text-[--color-dark-primary]'
                                                        : 'text-gray-900 dark:text-[--color-dark-text] hover:bg-gray-50 dark:hover:bg-[--color-dark-surface-hover]'
                                                }
                                                ${option.value === value ? 'bg-blue-100 dark:bg-[--color-dark-primary]/30' : ''}
                                            `.trim()}
                                            onClick={() => handleSelect(option)}
                                            onMouseEnter={() => setHighlightedIndex(index)}
                                            role="option"
                                            aria-selected={option.value === value}
                                        >
                                            <span className="block truncate">{option.label}</span>
                                            {option.value === value && (
                                                <CheckIcon className="h-4 w-4 text-blue-600 dark:text-[--color-dark-primary] shrink-0" />
                                            )}
                                        </li>
                                    ))
                                )}
                            </ul>
                        </div>
                    )}
                </div>

                {error && (
                    <p className="mt-1 text-sm text-red-600 dark:text-[--color-dark-danger]">{error}</p>
                )}
                {helperText && !error && (
                    <p className="mt-1 text-sm text-gray-500 dark:text-[--color-dark-text-secondary]">{helperText}</p>
                )}
            </div>
        );
    }
);

Select.displayName = 'Select';

export default Select;