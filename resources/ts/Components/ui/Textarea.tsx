import { TextareaHTMLAttributes, forwardRef } from 'react';

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    error?: string;
    helperText?: string;
}

const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
    ({ label, error, helperText, className = '', ...props }, ref) => {
        const baseClasses = 'w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200 resize-vertical';
        const errorClasses = error
            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
            : 'border-gray-300 focus:border-blue-500';

        const finalClassName = `${baseClasses} ${errorClasses} ${className}`;

        return (
            <div className="w-full">
                {label && (
                    <label className="block text-sm font-medium text-gray-700 mb-1">
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
                    <p className="mt-1 text-sm text-red-600">{error}</p>
                )}
                {helperText && !error && (
                    <p className="mt-1 text-sm text-gray-500">{helperText}</p>
                )}
            </div>
        );
    }
);

Textarea.displayName = 'Textarea';

export default Textarea;