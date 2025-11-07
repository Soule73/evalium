import { ButtonHTMLAttributes } from 'react';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    color?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning';
    variant?: 'solid' | 'outline' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    loading?: boolean;
    children: React.ReactNode;
}

export function Button({
    color = 'primary',
    variant = 'solid',
    size = 'md',
    loading = false,
    disabled,
    children,
    className = '',
    ...props
}: ButtonProps) {
    const baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed';

    const variantClasses = {
        solid: 'border border-transparent',
        outline: 'border',
        ghost: 'border border-transparent',
    };

    const colorClassesMap = {
        solid: {
            primary: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
            secondary: 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500',
            danger: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
            success: 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
            warning: 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500',
        },
        outline: {
            primary: 'bg-transparent border-blue-600 text-blue-600 hover:bg-blue-50 focus:ring-blue-500',
            secondary: 'bg-transparent border-gray-200 text-gray-600 hover:bg-gray-50 focus:ring-gray-500',
            danger: 'bg-transparent border-red-600 text-red-600 hover:bg-red-50 focus:ring-red-500',
            success: 'bg-transparent border-green-600 text-green-600 hover:bg-green-50 focus:ring-green-500',
            warning: 'bg-transparent border-yellow-600 text-yellow-600 hover:bg-yellow-50 focus:ring-yellow-500',
        },
        ghost: {
            primary: 'bg-transparent text-blue-600 hover:bg-blue-50 focus:ring-blue-500',
            secondary: 'bg-transparent text-gray-600 hover:bg-gray-50 focus:ring-gray-500',
            danger: 'bg-transparent text-red-600 hover:bg-red-50 focus:ring-red-500',
            success: 'bg-transparent text-green-600 hover:bg-green-50 focus:ring-green-500',
            warning: 'bg-transparent text-yellow-600 hover:bg-yellow-50 focus:ring-yellow-500',
        },
    };

    const colorClasses = colorClassesMap[variant][color];

    const sizeClasses = {
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
    };

    const finalClassName = `${baseClasses} ${variantClasses[variant]} ${colorClasses} ${sizeClasses[size]} ${className}`;

    return (
        <button
            {...props}
            className={`cursor-pointer ${finalClassName}`}
            disabled={disabled || loading}
        >
            {loading && (
                <svg
                    className="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        className="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        strokeWidth="4"
                    ></circle>
                    <path
                        className="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            )}
            {children}
        </button>
    );
}