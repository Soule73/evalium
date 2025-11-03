import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { NavIcon } from '../Navigation/NavIcon';
import { UserAvatar } from '../Navigation/UserAvatar';
import { RoleBadge } from '../Navigation/RoleBadge';
import { User, PageProps } from '@/types';
import LogoExamena from '../LogoExamena';
import { hasPermission } from '@/utils/permissions';
import { navRoutes } from '@/utils/breadcrumbs';

interface SidebarProps {
    currentPath: string;
    user: User;
}

interface NavItem {
    name: string;
    href: string;
    icon: 'dashboard' | 'exams' | 'users' | 'groups' | 'levels' | 'roles' | 'results' | 'manage-exams';
}

export const Sidebar = ({ currentPath, user }: SidebarProps) => {
    const { auth } = usePage<PageProps>().props;

    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isMobileOpen, setIsMobileOpen] = useState(false);

    const canViewUsers = hasPermission(auth.permissions, 'view users');

    const canViewGroups = hasPermission(auth.permissions, 'view groups');

    const canViewRoles = hasPermission(auth.permissions, 'view roles');

    const canViewLevels = hasPermission(auth.permissions, 'view levels');

    const canViewExams = hasPermission(auth.permissions, 'view exams');

    const canViewAnyExam = hasPermission(auth.permissions, 'view any exam');

    const canViewExamList = canViewExams || canViewAnyExam;

    const userRole = user.roles?.[0]?.name as 'super_admin' | 'admin' | 'teacher' | 'student' | undefined;

    const isStudent = userRole === 'student';

    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth >= 1024) {
                setIsMobileOpen(false);
            }
        };

        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    useEffect(() => {
        const saved = localStorage.getItem('sidebarCollapsed');
        if (saved !== null) {
            setIsCollapsed(saved === 'true');
        }
    }, []);

    const toggleCollapse = () => {
        const newState = !isCollapsed;
        setIsCollapsed(newState);
        localStorage.setItem('sidebarCollapsed', String(newState));
        window.dispatchEvent(new Event('sidebarToggle'));
    };

    const toggleMobile = () => {
        setIsMobileOpen(!isMobileOpen);
    };

    const getPathname = (url: string) => {
        try {
            return new URL(url).pathname;
        } catch {
            return url;
        }
    };

    const isActive = (href: string) => {

        const hrefPath = getPathname(href);
        return currentPath === hrefPath || currentPath.startsWith(hrefPath + '/');
    };

    const navItems: NavItem[] = [];

    navItems.push({ name: 'Tableau de bord', href: navRoutes.dashboard(), icon: 'dashboard' });

    if (isStudent) {
        navItems.push(
            { name: 'Mes Examens', href: navRoutes.studentExams(), icon: 'exams' }
        );
    }

    if (canViewExamList) {
        navItems.push(
            { name: 'Examens', href: navRoutes.exams(), icon: 'exams' }
        );
    }

    if (canViewUsers) {
        navItems.push({ name: 'Utilisateurs', href: navRoutes.users(), icon: 'users' });
    }

    if (canViewGroups) {
        navItems.push({ name: 'Groupes', href: navRoutes.groups(), icon: 'groups' });
    }

    if (canViewLevels) {
        navItems.push({ name: 'Niveaux', href: navRoutes.levels(), icon: 'levels' });
    }

    if (canViewRoles) {
        navItems.push({ name: 'Rôles & Permissions', href: navRoutes.roles(), icon: 'roles' });
    }

    return (
        <>
            {/* Bouton hamburger mobile */}
            <button
                onClick={toggleMobile}
                className="lg:hidden fixed top-4 left-4 z-3 p-2 rounded-md bg-white  hover:bg-gray-50"
                aria-label="Toggle menu"
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {isMobileOpen ? (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    ) : (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                    )}
                </svg>
            </button>

            {/* Overlay mobile */}
            {isMobileOpen && (
                <div
                    className="lg:hidden fixed inset-0 bg-gray-600 bg-opacity-75 z-40"
                    onClick={toggleMobile}
                />
            )}

            {/* Sidebar */}
            <aside
                className={`
                    fixed top-0 left-0 z-40 h-screen transition-all duration-300 ease-in-out bg-white border-r border-gray-200
                    ${isCollapsed ? 'lg:w-20' : 'lg:w-64'}
                    ${isMobileOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'}
                `}
            >
                <div className="flex flex-col h-full">
                    {/* Header avec logo */}
                    <div className="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                        {!isCollapsed && (
                            <Link href={navRoutes.dashboard()} className="flex items-center">
                                <LogoExamena />
                                <span className="ml-2 text-xl font-bold text-indigo-600">Examena</span>
                            </Link>
                        )}

                        {isCollapsed && (
                            <Link href={navRoutes.dashboard()} className="flex items-center justify-center w-full">
                                <LogoExamena width={32} height={32} />
                            </Link>
                        )}

                        {/* Bouton toggle collapse (desktop uniquement) */}
                        <button
                            onClick={toggleCollapse}
                            className="hidden lg:block cursor-pointer p-1.5 rounded-md hover:bg-gray-100 text-gray-500"
                            aria-label="Toggle sidebar"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                {isCollapsed ? (
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                ) : (
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                )}
                            </svg>
                        </button>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 px-3 py-4 space-y-1 custom-scrollbar overflow-y-auto">
                        {navItems.map((item) => {
                            const active = isActive(item.href);
                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`
                                        flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                                        ${active
                                            ? 'bg-indigo-50 text-indigo-600'
                                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                                        }
                                        ${isCollapsed ? 'justify-center' : ''}
                                    `}
                                    title={isCollapsed ? item.name : undefined}
                                    onClick={() => setIsMobileOpen(false)}
                                >
                                    <NavIcon type={item.icon} className={`w-5 h-5 ${!isCollapsed && 'mr-3'}`} />
                                    {!isCollapsed && <span>{item.name}</span>}
                                </Link>
                            );
                        })}
                    </nav>

                    <div className="border-t border-gray-200 bg-gray-50">
                        {!isCollapsed ? (
                            <>
                                <Link
                                    href={navRoutes.profile()}
                                    className={`
                                        flex items-center space-x-3 p-3 transition-all mb-3
                                        ${isActive(navRoutes.profile())
                                            ? 'bg-indigo-50 border-b border-indigo-200'
                                            : 'bg-white border-b border-gray-200 hover:border-indigo-200 hover:bg-indigo-50'
                                        }
                                    `}
                                >
                                    <UserAvatar name={user.name} size="md" />
                                    <div className="flex-1 min-w-0">
                                        <p className={`text-sm font-semibold truncate ${isActive(navRoutes.profile()) ? 'text-indigo-900' : 'text-gray-900'
                                            }`}>
                                            {user.name}
                                        </p>
                                        <div className="my-1">
                                            <RoleBadge role={userRole} />
                                        </div>
                                        <p className="text-xs text-gray-500 truncate">
                                            {user.email}
                                        </p>
                                    </div>
                                </Link>



                                <div className=' px-2 pb-3'>
                                    <Link
                                        href={navRoutes.logout()}
                                        method="post"
                                        as="button"
                                        className="w-full flex items-center justify-center space-x-2 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                                    >
                                        <NavIcon type="logout" className="w-4 h-4" />
                                        <span>Déconnexion</span>
                                    </Link>
                                </div>
                            </>
                        ) : (
                            <div className="p-2 space-y-2">
                                <Link
                                    href={navRoutes.profile()}
                                    className={`
                                        relative flex items-center justify-center p-2 rounded-lg transition-all
                                        ${isActive(navRoutes.profile())
                                            ? 'bg-indigo-50 ring-2 ring-indigo-600'
                                            : 'bg-white hover:bg-indigo-50'
                                        }
                                    `}
                                    title={`${user.name} - Profil`}
                                >
                                    <UserAvatar name={user.name} size="sm" />
                                    {isActive(navRoutes.profile()) && (
                                        <span className="absolute top-1 right-1 w-2 h-2 bg-indigo-600 rounded-full"></span>
                                    )}
                                </Link>

                                <Link
                                    href={navRoutes.logout()}
                                    method="post"
                                    as="button"
                                    className="w-full flex items-center justify-center p-2.5 text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                                    title="Déconnexion"
                                >
                                    <NavIcon type="logout" className="w-5 h-5" />
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </aside>
        </>
    );
};
