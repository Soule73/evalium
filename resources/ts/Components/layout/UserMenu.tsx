import { Link, router } from '@inertiajs/react';
import { UserAvatar } from './UserAvatar';
import { route } from 'ziggy-js';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface UserMenuProps {
    user: {
        name: string;
        email: string;
        roles?: Array<{ name: string }>;
    };
    isMobile?: boolean;
}

/**
 * Displays user information and logout action.
 * Currently only renders in mobile mode; desktop layout is handled by the sidebar.
 */
export const UserMenu = ({ user, isMobile = false }: UserMenuProps) => {
    const { t } = useTranslations();

    const handleClick = (e: React.MouseEvent) => {
        e.preventDefault();
        router.visit(route('profile'));
    };

    if (!isMobile) {
        return null;
    }

    return (
        <div className="pt-4 pb-3 border-t border-gray-200">
            <div className="flex items-center px-4 cursor-pointer" onClick={handleClick}>
                <UserAvatar name={user.name} size="lg" />
                <div className="ml-3">
                    <div className="text-base font-medium text-gray-800">{user.name}</div>
                    <div className="text-sm text-gray-500">{user.email}</div>
                </div>
            </div>
            <div className="mt-3 space-y-1">
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="block cursor-pointer px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100"
                >
                    {t('sidebar.navigation.logout')}
                </Link>
            </div>
        </div>
    );
};
