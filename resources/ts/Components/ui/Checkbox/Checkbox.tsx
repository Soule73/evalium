import { forwardRef } from "react";

type CheckboxProps = React.InputHTMLAttributes<HTMLInputElement> & {
    label?: string | React.ReactNode;
    type?: 'checkbox' | 'radio';
    error?: string;
    className?: string;
    labelClassName?: string;
};

/**
 * Checkbox/Radio component with custom styling
 * Supports both checkbox and radio types with proper accessibility
 */
const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
    ({ label, className = '', labelClassName = '', type = 'checkbox', id, error, ...props }, ref) => {
        const generatedId = id || `${type}-${Math.random().toString(36).substr(2, 9)}`;
        const errorId = error ? `${generatedId}-error` : undefined;
        const roundedClass = type === 'radio' ? 'rounded-full' : 'rounded-sm';

        const checkboxElement = (
            <span className="relative flex items-center">
                <input
                    ref={ref}
                    id={generatedId}
                    type={type}
                    className={`peer appearance-none w-5 h-5 border border-gray-300 ${className} ${roundedClass} checked:bg-indigo-600 checked:border-indigo-600 focus:ring-2 focus:ring-indigo-500 transition-colors duration-200 bg-white`}
                    aria-invalid={error ? 'true' : 'false'}
                    aria-describedby={errorId}
                    {...props}
                />
                <svg
                    className="absolute left-0 top-0 w-5 h-5 text-white pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity duration-150"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                >
                    <path d="M6 10l3 3 5-5" />
                </svg>
            </span>
        );

        return (
            <div>
                {label ? (
                    <label
                        htmlFor={generatedId}
                        className={`inline-flex items-center gap-2 cursor-pointer select-none ${labelClassName}`}
                    >
                        {checkboxElement}
                        {typeof label === 'string' ? (
                            <span className="text-sm text-gray-900 transition-colors duration-150">
                                {label}
                            </span>
                        ) : (
                            label
                        )}
                    </label>
                ) : (
                    checkboxElement
                )}
                {error && (
                    <p id={errorId} className="mt-1 text-sm text-red-600" role="alert">
                        {error}
                    </p>
                )}
            </div>
        );
    }
);

Checkbox.displayName = 'Checkbox';

export default Checkbox;
