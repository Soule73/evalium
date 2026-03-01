import { Link } from '@inertiajs/react';
import { ChevronRightIcon, HomeIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { useTranslations } from '@/hooks/shared/useTranslations';

export interface BreadcrumbItem {
    label: string;
    href?: string;
    icon?: React.ReactNode;
}

interface BreadcrumbProps {
    items: BreadcrumbItem[];
}

export const Breadcrumb = ({ items }: BreadcrumbProps) => {
    const { t } = useTranslations();

    return (
        <nav
            className="flex items-center text-sm min-w-0 overflow-x-auto scrollbar-none"
            aria-label="Breadcrumb"
        >
            <Link
                href={route('dashboard')}
                className="flex items-center shrink-0 text-gray-500 hover:text-gray-700 transition-colors"
            >
                <HomeIcon className="w-4 h-4" />
                <span className="sr-only">{t('Home')}</span>
            </Link>

            {items.map((item, index) => {
                const isLast = index === items.length - 1;

                return (
                    <div key={index} className="flex items-center min-w-0 shrink-0 last:shrink">
                        <ChevronRightIcon className="w-4 h-4 mx-1.5 text-gray-400 shrink-0" />

                        {isLast || !item.href ? (
                            <span className="flex items-center gap-1 font-medium text-gray-900 min-w-0">
                                {item.icon && <span className="shrink-0">{item.icon}</span>}
                                <span className="truncate">{item.label}</span>
                            </span>
                        ) : (
                            <Link
                                href={item.href}
                                className="flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors whitespace-nowrap"
                            >
                                {item.icon && <span className="shrink-0">{item.icon}</span>}
                                <span className="truncate max-w-32 sm:max-w-48">{item.label}</span>
                            </Link>
                        )}
                    </div>
                );
            })}
        </nav>
    );
};
