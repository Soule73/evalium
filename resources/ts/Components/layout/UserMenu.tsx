import { Link, router } from '@inertiajs/react';
import { RoleBadge } from './RoleBadge';
import { UserAvatar } from './UserAvatar';
import { NavIcon } from './NavIcon';
import { route } from 'ziggy-js';

interface UserMenuProps {
    user: {
        name: string;
        email: string;
        roles?: Array<{ name: string }>;
    };
    isAdmin: boolean;
    isTeacher: boolean;
    isMobile?: boolean;
}

export const UserMenu = ({ user, isAdmin, isTeacher, isMobile = false }: UserMenuProps) => {
    const userRole = isAdmin ? 'admin' : isTeacher ? 'teacher' : 'student';

    const handleClick = (e: React.MouseEvent) => {
        e.preventDefault();
        router.visit(route('profile'));
    }

    if (isMobile) {
        return (
            <div className="pt-4 pb-3 border-t border-gray-200">
                <div className="flex items-center px-4 cursor-pointer" onClick={handleClick}>
                    <UserAvatar name={user.name} size="lg" />
                    <div className="ml-3">
                        <div className="text-base font-medium text-gray-800">
                            {user.name}
                        </div>
                        <div className="text-sm text-gray-500">
                            {user.email}
                        </div>
                    </div>
                </div>
                <div className="mt-3 space-y-1">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="block cursor-pointer px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100"
                    >
                        Déconnexion
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="hidden sm:flex sm:items-center sm:ml-6">
            <div className="flex items-center space-x-4">
                {/* Role Badge */}
                <RoleBadge role={userRole} />

                {/* User info */}
                <div className="flex items-center space-x-3 cursor-pointer" onClick={handleClick}>
                    <div className="text-right">
                        <div className="text-sm font-medium text-gray-900">
                            {user.name}
                        </div>
                        <div className="text-xs text-gray-500">
                            {user.email}
                        </div>
                    </div>

                    <UserAvatar name={user.name} />
                </div>

                {/* Logout button */}
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="flex cursor-pointer items-center space-x-1 text-gray-500 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                >
                    <NavIcon type="logout" />
                    <span>Déconnexion</span>
                </Link>
            </div>
        </div>
    );
};