import { ChevronUpIcon } from "@heroicons/react/24/outline";
import React from "react";

interface SectionProps {
    title: React.ReactNode;
    subtitle?: React.ReactNode;
    actions?: React.ReactNode;
    children: React.ReactNode;
    collapsible?: boolean;
    defaultOpen?: boolean;
    className?: string;
    centerHeaderItems?: boolean;
}

/**
 * Section component with optional collapsible functionality
 * Provides a consistent layout for content sections with title, subtitle, and actions
 */
const Section = ({
    title,
    subtitle,
    actions,
    children,
    collapsible = false,
    defaultOpen = true,
    className = '',
    centerHeaderItems = true
}: SectionProps) => {
    const [isOpen, setIsOpen] = React.useState(defaultOpen);
    const isStringTitle = typeof title === 'string';
    const shouldShowContent = !collapsible || isOpen;

    const toggleSection = () => {
        if (collapsible) {
            setIsOpen(prev => !prev);
        }
    };

    const headerClasses = [
        'text-gray-800 transition-all duration-200',
        shouldShowContent ? 'mb-4 border-b pb-2 border-gray-300' : 'mb-0'
    ].join(' ');

    const containerClasses = [
        centerHeaderItems ? 'md:items-center' : '',
        actions ? 'flex space-y-4 flex-col md:flex-row md:justify-between mb-2' : 'mb-2'
    ].join(' ');

    const titleWrapperClasses = [
        'flex items-center space-x-2',
        collapsible ? 'cursor-pointer select-none hover:text-blue-600 transition-colors duration-150' : ''
    ].join(' ');

    const sectionClasses = [
        'bg-white rounded-lg border border-gray-200 mb-6 transition-all duration-200',
        shouldShowContent ? 'p-2 md:p-6' : 'p-2 md:px-6 md:py-4',
        className
    ].join(' ');

    const chevronClasses = [
        'h-5 w-5 transition-transform duration-200',
        isOpen ? 'rotate-0' : 'rotate-180'
    ].join(' ');

    return (
        <section className={sectionClasses}>
            <div className={headerClasses}>
                <div className={containerClasses}>
                    <div onClick={toggleSection} className={titleWrapperClasses}>
                        {collapsible && (
                            <ChevronUpIcon className={chevronClasses} aria-hidden="true" />
                        )}
                        {isStringTitle ? (
                            <h2 className="text-xl font-semibold text-gray-800">
                                {title}
                            </h2>
                        ) : (
                            title
                        )}
                    </div>

                    {actions && (
                        <div className="shrink-0">
                            {actions}
                        </div>
                    )}
                </div>

                {shouldShowContent && subtitle && (
                    <div className="text-sm text-gray-600 mt-2">
                        {subtitle}
                    </div>
                )}
            </div>

            {shouldShowContent && (
                <div className="space-y-6 animate-in fade-in duration-200">
                    {children}
                </div>
            )}
        </section>
    );
};

export default Section;
