import { Link } from '@inertiajs/react';
import { NavIcon, type NavIconType } from './NavIcon';

interface NavLinkProps {
    href: string;
    icon?: NavIconType;
    children: React.ReactNode;
    isActive?: boolean;
    variant?: 'desktop' | 'mobile';
    className?: string;
}

export const NavLink = ({
    href,
    icon,
    children,
    isActive = false,
    variant = 'desktop',
    className = ''
}: NavLinkProps) => {
    const desktopClasses = `whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center ${isActive
        ? 'border-blue-500 text-blue-600'
        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
        }`;

    const mobileClasses = `block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200 ${isActive
        ? 'bg-blue-100 text-blue-700'
        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
        }`;

    const linkClasses = variant === 'desktop' ? desktopClasses : mobileClasses;

    return (
        <Link href={href} className={`${linkClasses} ${className}`}>
            {icon && variant === 'desktop' ? (
                <div className="flex items-center space-x-1">
                    <NavIcon type={icon} />
                    <span>{children}</span>
                </div>
            ) : (
                children
            )}
        </Link>
    );
};