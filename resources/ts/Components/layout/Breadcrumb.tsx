import { Link } from '@inertiajs/react';
import { ChevronRightIcon, HomeIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';

export interface BreadcrumbItem {
    label: string;
    href?: string;
    icon?: React.ReactNode;
}

interface BreadcrumbProps {
    items: BreadcrumbItem[];
}

export const Breadcrumb = ({ items }: BreadcrumbProps) => {
    return (
        <nav className="flex items-center space-x-2 text-sm" aria-label="Breadcrumb">
            <Link
                href={route('dashboard')}
                className="flex items-center text-gray-500 hover:text-gray-700 transition-colors"
            >
                <HomeIcon className="w-4 h-4" />
                <span className="sr-only">Accueil</span>
            </Link>

            {items.map((item, index) => {
                const isLast = index === items.length - 1;

                return (
                    <div key={index} className="flex items-center space-x-2">
                        <ChevronRightIcon className="w-4 h-4 text-gray-400" />

                        {isLast || !item.href ? (
                            <span className="flex items-center space-x-1 font-medium text-gray-900">
                                {item.icon && <span className="shrink-0">{item.icon}</span>}
                                <span className="truncate">{item.label}</span>
                            </span>
                        ) : (
                            <Link
                                href={item.href}
                                className="flex items-center space-x-1 text-gray-500 hover:text-gray-700 transition-colors"
                            >
                                {item.icon && <span className="shrink-0">{item.icon}</span>}
                                <span className="truncate">{item.label}</span>
                            </Link>
                        )}
                    </div>
                );
            })}
        </nav>
    );
};
