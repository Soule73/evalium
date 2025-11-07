import { forwardRef } from "react";

type CustomCheckboxProps = React.InputHTMLAttributes<HTMLInputElement> & {
    label?: string | React.ReactNode;
    type?: 'checkbox' | 'radio';
    error?: string;
    className?: string;
    labelClassName?: string;
};


const Checkbox = forwardRef<HTMLInputElement, CustomCheckboxProps>(
    ({ label, className = '', labelClassName = '', type = 'checkbox', ...props }, ref) => {
        const roundedClass = type === 'radio' ? 'rounded-full' : 'rounded-sm';

        const checkboxElement = (
            <span className="relative flex items-center">
                <input
                    ref={ref}
                    type={type}
                    className={`peer appearance-none w-5 h-5 border border-gray-300 ${className} ${roundedClass} checked:bg-blue-600 checked:border-blue-600 focus:ring-2 focus:ring-blue-500 transition-colors duration-200`}
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
                    <label className={`inline-flex items-center gap-2 cursor-pointer select-none ${labelClassName}`}>
                        {checkboxElement}
                        {typeof label === 'string' ? (
                            <span className="text-sm text-gray-900 transition-colors duration-150 peer-checked:text-blue-600">
                                {label}
                            </span>
                        ) : (
                            label
                        )}
                    </label>
                ) : (
                    checkboxElement
                )}
                {props.error && <p className="mt-1 text-sm text-red-600">{props.error}</p>}
            </div>
        );
    }
);

export default Checkbox;