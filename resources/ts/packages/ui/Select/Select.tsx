import { useState, useRef, useEffect, useCallback, forwardRef } from 'react';
import { ChevronDownIcon, CheckIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';

interface Option {
    id?: string | number;
    value: string | number;
    label: string;
    disabled?: boolean;
}

interface SelectProps {
    id?: string;
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
    size?: 'sm' | 'md';
    className?: string;
    name?: string;
    required?: boolean;
}

const SIZE_STYLES = {
    sm: {
        trigger: 'px-2.5 py-1.5 text-sm',
        option: 'px-2.5 py-1.5 text-xs',
        icon: 'h-4 w-4',
        chevron: 'h-4 w-4',
    },
    md: {
        trigger: 'px-3 py-2 text-sm',
        option: 'px-3 py-2 text-sm',
        icon: 'h-5 w-5',
        chevron: 'h-5 w-5',
    },
} as const;

const Select = forwardRef<HTMLDivElement, SelectProps>(
    (
        {
            id,
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
            size = 'md',
            className = '',
            required = false,
            name,
        },
        ref,
    ) => {
        const [isOpen, setIsOpen] = useState(false);
        const [searchTerm, setSearchTerm] = useState('');
        const [highlightedIndex, setHighlightedIndex] = useState(-1);

        const dropdownRef = useRef<HTMLDivElement>(null);
        const searchInputRef = useRef<HTMLInputElement>(null);
        const listRef = useRef<HTMLUListElement>(null);

        const sz = SIZE_STYLES[size];

        const filteredOptions =
            searchable && searchTerm
                ? options.filter((o) => o.label.toLowerCase().includes(searchTerm.toLowerCase()))
                : options;

        const selectedOption = options.find((o) => o.value === value);

        const close = useCallback(() => {
            setIsOpen(false);
            setSearchTerm('');
            setHighlightedIndex(-1);
        }, []);

        useEffect(() => {
            const handleClickOutside = (e: MouseEvent) => {
                if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
                    close();
                    onBlur?.();
                }
            };
            document.addEventListener('mousedown', handleClickOutside);
            return () => document.removeEventListener('mousedown', handleClickOutside);
        }, [close, onBlur]);

        useEffect(() => {
            if (isOpen && searchable) {
                searchInputRef.current?.focus();
            }
        }, [isOpen, searchable]);

        useEffect(() => {
            if (highlightedIndex >= 0 && listRef.current) {
                const el = listRef.current.children[highlightedIndex] as HTMLElement;
                el?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }, [highlightedIndex]);

        const handleSelect = useCallback(
            (option: Option) => {
                if (option.disabled) return;
                onChange?.(option.value);
                close();
            },
            [onChange, close],
        );

        const handleToggle = useCallback(() => {
            if (disabled) return;
            setIsOpen((prev) => !prev);
        }, [disabled]);

        const handleKeyDown = useCallback(
            (e: React.KeyboardEvent) => {
                if (!isOpen) {
                    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        setIsOpen(true);
                    }
                    return;
                }
                switch (e.key) {
                    case 'Escape':
                        close();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        setHighlightedIndex((prev) =>
                            prev < filteredOptions.length - 1 ? prev + 1 : prev,
                        );
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        setHighlightedIndex((prev) => (prev > 0 ? prev - 1 : prev));
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (highlightedIndex >= 0 && filteredOptions[highlightedIndex]) {
                            handleSelect(filteredOptions[highlightedIndex]);
                        }
                        break;
                }
            },
            [isOpen, close, filteredOptions, highlightedIndex, handleSelect],
        );

        const triggerClasses = [
            'w-full bg-white border rounded-md cursor-pointer flex items-center justify-between',
            'focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200',
            sz.trigger,
            error
                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                : 'border-gray-300 focus:border-indigo-500 hover:border-gray-400',
            disabled ? 'bg-gray-50 cursor-not-allowed opacity-60' : '',
            isOpen ? 'ring-2 ring-indigo-500 border-indigo-500' : '',
        ]
            .filter(Boolean)
            .join(' ');

        return (
            <div
                className={`relative w-full ${className}`}
                ref={ref}
                id={id}
                data-e2e={id ? `${id}-container` : undefined}
            >
                <input type="hidden" name={name} value={value ?? ''} />

                {label && (
                    <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">
                        {label}
                        {required && <span className="text-red-500 ml-1">*</span>}
                    </label>
                )}

                <div className="relative" ref={dropdownRef}>
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
                        <span
                            className={`block truncate ${!selectedOption ? 'text-gray-500' : 'text-gray-900'}`}
                        >
                            {selectedOption ? selectedOption.label : (placeholder ?? '')}
                        </span>
                        <ChevronDownIcon
                            className={`${sz.chevron} text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                        />
                    </div>

                    {isOpen && (
                        <div className="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                            {searchable && (
                                <div className="p-2 border-b border-gray-200">
                                    <div className="relative">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <input
                                            ref={searchInputRef}
                                            type="text"
                                            className="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white text-gray-900"
                                            placeholder={searchPlaceholder}
                                            value={searchTerm}
                                            onChange={(e) => {
                                                setSearchTerm(e.target.value);
                                                setHighlightedIndex(-1);
                                            }}
                                            onKeyDown={handleKeyDown}
                                        />
                                    </div>
                                </div>
                            )}

                            <ul
                                ref={listRef}
                                className="max-h-60 overflow-auto custom-scrollbar py-1"
                                role="listbox"
                            >
                                {filteredOptions.length === 0 ? (
                                    <li className="px-3 py-2 text-sm text-gray-500 text-center">
                                        {noOptionFound}
                                    </li>
                                ) : (
                                    filteredOptions.map((option, index) => {
                                        const isSelected = option.value === value;
                                        const isHighlighted = index === highlightedIndex;
                                        const optionClasses = [
                                            sz.option,
                                            'cursor-pointer flex items-center justify-between transition-colors duration-150',
                                            option.disabled
                                                ? 'text-gray-400 cursor-not-allowed'
                                                : isHighlighted
                                                  ? 'bg-indigo-50 text-indigo-900'
                                                  : 'text-gray-900 hover:bg-gray-50',
                                            isSelected ? 'bg-indigo-100' : '',
                                        ]
                                            .filter(Boolean)
                                            .join(' ');

                                        return (
                                            <li
                                                key={option.value}
                                                className={optionClasses}
                                                onClick={() => handleSelect(option)}
                                                onMouseEnter={() => setHighlightedIndex(index)}
                                                role="option"
                                                aria-selected={isSelected}
                                            >
                                                <span className="block truncate">
                                                    {option.label}
                                                </span>
                                                {isSelected && (
                                                    <CheckIcon
                                                        className={`${sz.icon} text-indigo-600 shrink-0`}
                                                    />
                                                )}
                                            </li>
                                        );
                                    })
                                )}
                            </ul>
                        </div>
                    )}
                </div>

                {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
                {helperText && !error && <p className="mt-1 text-sm text-gray-500">{helperText}</p>}
            </div>
        );
    },
);

Select.displayName = 'Select';

export default Select;
