import { TextareaHTMLAttributes, forwardRef } from 'react';

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    error?: string;
    helperText?: string;
}

const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
    ({ label, error, helperText, className = '', ...props }, ref) => {
        const baseClasses = 'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-[--color-dark-primary] transition-colors duration-200 resize-vertical bg-white dark:bg-[--color-dark-surface] text-gray-900 dark:text-[--color-dark-text]';
        const errorClasses = error
            ? 'border-red-500 dark:border-[--color-dark-danger] focus:border-red-500 dark:focus:border-[--color-dark-danger] focus:ring-red-500 dark:focus:ring-[--color-dark-danger]'
            : 'border-gray-300 dark:border-[--color-dark-border] focus:border-blue-500 dark:focus:border-[--color-dark-primary]';

        const finalClassName = `${baseClasses} ${errorClasses} ${className}`;

        return (
            <div className="w-full">
                {label && (
                    <label className="block text-sm font-medium text-gray-700 dark:text-[--color-dark-text] mb-1">
                        {label}
                    </label>
                )}
                <textarea
                    ref={ref}
                    className={finalClassName}
                    rows={4}
                    {...props}
                />
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

Textarea.displayName = 'Textarea';

export default Textarea;