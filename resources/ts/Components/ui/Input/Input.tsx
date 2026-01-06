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

        const baseClasses = 'w-full px-3 py-2 bg-white text-gray-900 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200';
        const errorClasses = error
            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500';

        const finalClassName = `${baseClasses} ${errorClasses} ${className}`;

        return (
            <div className="w-full">
                {label && (
                    <label
                        htmlFor={generatedId}
                        className="block text-sm font-medium text-gray-700 mb-1"
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
                    <p id={errorId} className="mt-1 text-sm text-red-600" role="alert">
                        {error}
                    </p>
                )}
                {helperText && !error && (
                    <p id={helperId} className="mt-1 text-sm text-gray-500">
                        {helperText}
                    </p>
                )}
            </div>
        );
    }
);

Input.displayName = 'Input';

export default Input;
