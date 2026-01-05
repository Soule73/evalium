import { useState, useEffect } from 'react';

interface ToggleProps {
    checked?: boolean;
    onChange?: (checked: boolean) => void;
    disabled?: boolean;
    size?: 'sm' | 'md' | 'lg';
    color?: 'blue' | 'green' | 'purple' | 'red' | 'gray';
    label?: string;
    showLabel?: boolean;
    activeLabel?: string;
    inactiveLabel?: string;
    className?: string;
    name?: string;
    id?: string;
}

const sizeClasses = {
    sm: {
        container: 'h-5 w-9',
        button: 'h-3 w-3',
        translateOn: 'translate-x-5',
        translateOff: 'translate-x-1',
    },
    md: {
        container: 'h-6 w-11',
        button: 'h-4 w-4',
        translateOn: 'translate-x-6',
        translateOff: 'translate-x-1',
    },
    lg: {
        container: 'h-7 w-14',
        button: 'h-5 w-5',
        translateOn: 'translate-x-7',
        translateOff: 'translate-x-1',
    },
};

const colorClasses = {
    blue: {
        active: 'bg-blue-600 dark:bg-[--color-dark-primary]',
        inactive: 'bg-gray-200 dark:bg-[--color-dark-border]',
        focus: 'focus:ring-blue-500 dark:focus:ring-[--color-dark-primary]',
        labelActive: 'text-blue-700 dark:text-[--color-dark-primary]',
        labelInactive: 'text-gray-500 dark:text-[--color-dark-text-muted]',
    },
    green: {
        active: 'bg-green-600 dark:bg-[--color-dark-success]',
        inactive: 'bg-gray-200 dark:bg-[--color-dark-border]',
        focus: 'focus:ring-green-500 dark:focus:ring-[--color-dark-success]',
        labelActive: 'text-green-700 dark:text-[--color-dark-success]',
        labelInactive: 'text-gray-500 dark:text-[--color-dark-text-muted]',
    },
    purple: {
        active: 'bg-purple-600',
        inactive: 'bg-gray-200 dark:bg-[--color-dark-border]',
        focus: 'focus:ring-purple-500',
        labelActive: 'text-purple-700',
        labelInactive: 'text-gray-500 dark:text-[--color-dark-text-muted]',
    },
    red: {
        active: 'bg-red-600 dark:bg-[--color-dark-danger]',
        inactive: 'bg-gray-200 dark:bg-[--color-dark-border]',
        focus: 'focus:ring-red-500 dark:focus:ring-[--color-dark-danger]',
        labelActive: 'text-red-700 dark:text-[--color-dark-danger]',
        labelInactive: 'text-gray-500 dark:text-[--color-dark-text-muted]',
    },
    gray: {
        active: 'bg-gray-600 dark:bg-[--color-dark-secondary]',
        inactive: 'bg-gray-200 dark:bg-[--color-dark-border]',
        focus: 'focus:ring-gray-500 dark:focus:ring-[--color-dark-secondary]',
        labelActive: 'text-gray-700 dark:text-[--color-dark-text]',
        labelInactive: 'text-gray-500 dark:text-[--color-dark-text-muted]',
    },
};

/**
 * Toggle switch component with various sizes and colors
 * Implements proper accessibility with ARIA switch role
 */
export default function Toggle({
    checked = false,
    onChange,
    disabled = false,
    size = 'md',
    color = 'blue',
    label,
    showLabel = false,
    activeLabel = 'Active',
    inactiveLabel = 'Inactive',
    className = '',
    name,
    id,
}: ToggleProps) {
    const [isChecked, setIsChecked] = useState(checked);
    const generatedId = id || `toggle-${Math.random().toString(36).substr(2, 9)}`;

    useEffect(() => {
        setIsChecked(checked);
    }, [checked]);

    const handleToggle = () => {
        if (disabled) return;

        const newValue = !isChecked;
        setIsChecked(newValue);
        onChange?.(newValue);
    };

    const sizeClass = sizeClasses[size];
    const colorClass = colorClasses[color];

    return (
        <div className={`flex items-center gap-2 ${className}`}>
            {label && (
                <label
                    htmlFor={generatedId}
                    className="text-sm font-medium text-gray-700 dark:text-[--color-dark-text] mr-2"
                >
                    {label}
                </label>
            )}

            <button
                type="button"
                role="switch"
                id={generatedId}
                aria-checked={isChecked}
                aria-label={label || (isChecked ? activeLabel : inactiveLabel)}
                onClick={handleToggle}
                disabled={disabled}
                className={`
                    relative inline-flex ${sizeClass.container} items-center rounded-full 
                    transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 
                    ${isChecked ? colorClass.active : colorClass.inactive}
                    ${colorClass.focus}
                    ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                `}
                title={isChecked ? activeLabel : inactiveLabel}
            >
                <span
                    className={`
                        inline-block ${sizeClass.button} transform rounded-full bg-white 
                        transition-transform shadow-sm
                        ${isChecked ? sizeClass.translateOn : sizeClass.translateOff}
                    `}
                />
            </button>

            {name && (
                <input
                    type="hidden"
                    name={name}
                    value={isChecked ? '1' : '0'}
                />
            )}

            {showLabel && (
                <span
                    className={`
                        text-xs font-medium 
                        ${isChecked ? colorClass.labelActive : colorClass.labelInactive}
                    `}
                    aria-live="polite"
                >
                    {isChecked ? activeLabel : inactiveLabel}
                </span>
            )}
        </div>
    );
}
