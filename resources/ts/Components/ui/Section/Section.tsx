import { ChevronUpIcon } from "@heroicons/react/24/outline";
import { useState, useId } from "react";

interface SectionProps {
    title: React.ReactNode;
    subtitle?: React.ReactNode;
    actions?: React.ReactNode;
    children: React.ReactNode;
    collapsible?: boolean;
    defaultOpen?: boolean;
    className?: string;
    variant?: 'elevated' | 'flat';
}

/**
 * Reusable section component with optional collapsible functionality.
 * Provides consistent layout for content sections with title, subtitle, and actions.
 */
const Section = ({
    title,
    subtitle,
    actions,
    children,
    collapsible = false,
    defaultOpen = true,
    className = '',
    variant = 'elevated'
}: SectionProps) => {
    const [isOpen, setIsOpen] = useState(defaultOpen);
    const contentId = useId();
    const isVisible = !collapsible || isOpen;

    const variantStyles = {
        elevated: 'bg-white rounded-lg',
        flat: 'bg-transparent'
    };

    const paddingStyles = {
        elevated: isVisible ? 'p-4 md:p-6' : 'px-4 py-3 md:px-6',
        flat: isVisible ? 'py-4' : 'py-3'
    };

    return (
        <section className={`mb-6 ${variantStyles[variant]} ${paddingStyles[variant]} ${className}`.trim()}>
            <header className={`${isVisible ? 'mb-4 border-b border-gray-200 pb-3' : ''}`}>
                <div className={`${actions ? 'flex flex-col gap-3 md:flex-row md:items-center md:justify-between' : ''}`}>
                    <div
                        role={collapsible ? 'button' : undefined}
                        tabIndex={collapsible ? 0 : undefined}
                        aria-expanded={collapsible ? isOpen : undefined}
                        aria-controls={collapsible ? contentId : undefined}
                        onClick={collapsible ? () => setIsOpen(prev => !prev) : undefined}
                        onKeyDown={collapsible ? (e) => e.key === 'Enter' && setIsOpen(prev => !prev) : undefined}
                        className={`flex items-center gap-2 ${collapsible ? 'cursor-pointer select-none hover:text-indigo-600 transition-colors' : ''}`}
                    >
                        {collapsible && (
                            <ChevronUpIcon
                                className={`size-5 transition-transform duration-200 ${isOpen ? '' : 'rotate-180'}`}
                                aria-hidden="true"
                            />
                        )}
                        {typeof title === 'string' ? (
                            <h2 className="text-lg font-semibold text-gray-800">{title}</h2>
                        ) : (
                            title
                        )}
                    </div>
                    {actions && <div className="shrink-0">{actions}</div>}
                </div>
                {isVisible && subtitle && (
                    <p className="mt-1 text-sm text-gray-500">{subtitle}</p>
                )}
            </header>

            {isVisible && (
                <div id={contentId} className="space-y-4">
                    {children}
                </div>
            )}
        </section>
    );
};

export default Section;
