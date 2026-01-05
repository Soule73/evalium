import { InputHTMLAttributes, forwardRef } from 'react';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    helperText?: string;
}

/**
 * Input component with label, error, and helper text support
 * Follows accessibility best practices with proper labeling
 */
const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ label, error, helperText, className = '', id, ...props }, ref) => {
        const generatedId = id || `input-${Math.random().toString(36).substr(2, 9)}`;
        const errorId = error ? `${generatedId}-error` : undefined;
        const helperId = helperText ? `${generatedId}-helper` : undefined;

        const baseClasses = 'w-full px-3 py-2 bg-white dark:bg-[--color-dark-surface] text-gray-900 dark:text-[--color-dark-text] border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-[--color-dark-primary] transition-colors duration-200';
        const errorClasses = error
            ? 'border-red-500 dark:border-[--color-dark-danger] focus:border-red-500 dark:focus:border-[--color-dark-danger] focus:ring-red-500 dark:focus:ring-[--color-dark-danger]'
            : 'border-gray-300 dark:border-[--color-dark-border] focus:border-blue-500 dark:focus:border-[--color-dark-primary]';

        const finalClassName = `${baseClasses} ${errorClasses} ${className}`;

        return (
            <div className="w-full">
                {label && (
                    <label
                        htmlFor={generatedId}
                        className="block text-sm font-medium text-gray-700 dark:text-[--color-dark-text] mb-1"
                    >
                        {label}
                    </label>
                )}
                <input
                    ref={ref}
                    id={generatedId}
                    className={finalClassName}
                    aria-invalid={error ? 'true' : 'false'}
                    aria-describedby={[errorId, helperId].filter(Boolean).join(' ') || undefined}
                    {...props}
                />
                {error && (
                    <p id={errorId} className="mt-1 text-sm text-red-600 dark:text-[--color-dark-danger]" role="alert">
                        {error}
                    </p>
                )}
                {helperText && !error && (
                    <p id={helperId} className="mt-1 text-sm text-gray-500 dark:text-[--color-dark-text-secondary]">
                        {helperText}
                    </p>
                )}
            </div>
        );
    }
);

Input.displayName = 'Input';

export default Input;
