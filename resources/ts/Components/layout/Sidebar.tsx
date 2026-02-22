import { useState, useEffect, useMemo, useCallback } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { type User, type PageProps } from '@/types';
import { hasPermission, navRoutes } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { type NavIconType, NavIcon } from './NavIcon';
import { Logo } from './Logo';
import { RoleBadge } from './RoleBadge';
import { UserAvatar } from './UserAvatar';
import { Tooltip } from '@evalium/ui';
import { route } from 'ziggy-js';

interface NavItem {
    name: string;
    href: string;
    icon: NavIconType;
}

interface NavGroup {
    key: string;
    label?: string;
    items: NavItem[];
}

interface SidebarProps {
    currentPath: string;
    user: User;
}

export const Sidebar = ({ currentPath, user }: SidebarProps) => {
    const { auth } = usePage<PageProps>().props;
    const { t } = useTranslations();

    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isMobileOpen, setIsMobileOpen] = useState(false);

    const userRole = user.roles?.[0]?.name as
        | 'super_admin'
        | 'admin'
        | 'teacher'
        | 'student'
        | undefined;

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

    const toggleCollapse = useCallback(() => {
        setIsCollapsed((prev) => {
            const next = !prev;
            localStorage.setItem('sidebarCollapsed', String(next));
            window.dispatchEvent(new Event('sidebarToggle'));
            return next;
        });
    }, []);

    const toggleMobile = useCallback(() => {
        setIsMobileOpen((prev) => !prev);
    }, []);

    const closeMobile = useCallback(() => {
        setIsMobileOpen(false);
    }, []);

    const getPathname = useCallback((url: string): string => {
        try {
            return new URL(url).pathname;
        } catch {
            return url;
        }
    }, []);

    const isActive = useCallback(
        (href: string): boolean => {
            const hrefPath = getPathname(href);
            return currentPath === hrefPath || currentPath.startsWith(hrefPath + '/');
        },
        [currentPath, getPathname],
    );

    const navGroups = useMemo((): NavGroup[] => {
        const groups: NavGroup[] = [];
        const isStudent = userRole === 'student';
        const isTeacher = userRole === 'teacher';
        const isAdmin = userRole === 'admin' || userRole === 'super_admin';

        groups.push({
            key: 'main',
            items: [
                {
                    name: t('sidebar.navigation.dashboard'),
                    href: navRoutes.dashboard(),
                    icon: 'dashboard',
                },
            ],
        });

        if (isStudent) {
            groups.push({
                key: 'student',
                label: t('sidebar.groups.my_space'),
                items: [
                    {
                        name: t('sidebar.navigation.my_assessments'),
                        href: navRoutes.studentAssessments(),
                        icon: 'assessments',
                    },
                    {
                        name: t('sidebar.navigation.my_enrollment'),
                        href: navRoutes.studentEnrollment(),
                        icon: 'enrollment',
                    },
                ],
            });
        }

        if (isTeacher) {
            groups.push({
                key: 'teaching',
                label: t('sidebar.groups.teaching'),
                items: [
                    {
                        name: t('sidebar.navigation.assessments'),
                        href: navRoutes.teacherAssessments(),
                        icon: 'assessments',
                    },
                    {
                        name: t('sidebar.navigation.my_classes'),
                        href: navRoutes.teacherClasses(),
                        icon: 'classes',
                    },
                    {
                        name: t('sidebar.navigation.class_subjects'),
                        href: navRoutes.teacherClassSubjects(),
                        icon: 'class-subjects',
                    },
                ],
            });
        }

        if (isAdmin) {
            groups.push({
                key: 'academic',
                label: t('sidebar.groups.academic'),
                items: [
                    {
                        name: t('sidebar.navigation.assessments'),
                        href: navRoutes.adminAssessments(),
                        icon: 'assessments',
                    },
                    {
                        name: t('sidebar.navigation.subjects'),
                        href: route('admin.subjects.index'),
                        icon: 'subjects',
                    },
                    {
                        name: t('sidebar.navigation.classes'),
                        href: route('admin.classes.index'),
                        icon: 'classes',
                    },
                    {
                        name: t('sidebar.navigation.enrollments'),
                        href: route('admin.enrollments.index'),
                        icon: 'enrollment',
                    },
                    {
                        name: t('sidebar.navigation.class_subjects'),
                        href: route('admin.class-subjects.index'),
                        icon: 'class-subjects',
                    },
                ],
            });
        }

        const configItems: NavItem[] = [];
        if (isAdmin) {
            configItems.push({
                name: t('sidebar.navigation.archives'),
                href: navRoutes.adminAcademicYears(),
                icon: 'academic-years',
            });
        }
        if (hasPermission(auth.permissions, 'view users')) {
            configItems.push({
                name: t('sidebar.navigation.admins'),
                href: navRoutes.users(),
                icon: 'users',
            });
            configItems.push({
                name: t('sidebar.navigation.teachers'),
                href: navRoutes.teachers(),
                icon: 'users',
            });
        }
        if (hasPermission(auth.permissions, 'view levels')) {
            configItems.push({
                name: t('sidebar.navigation.levels'),
                href: navRoutes.levels(),
                icon: 'levels',
            });
        }
        if (hasPermission(auth.permissions, 'view roles')) {
            configItems.push({
                name: t('sidebar.navigation.roles_permissions'),
                href: navRoutes.roles(),
                icon: 'roles',
            });
        }

        if (configItems.length > 0) {
            groups.push({
                key: 'config',
                label: t('sidebar.groups.configuration'),
                items: configItems,
            });
        }

        return groups;
    }, [userRole, auth.permissions, t]);

    return (
        <>
            <button
                onClick={toggleMobile}
                className="lg:hidden fixed top-4 left-4 z-3 p-2 rounded-md bg-white hover:bg-gray-50"
                aria-label={t('sidebar.actions.toggle_menu')}
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {isMobileOpen ? (
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M6 18L18 6M6 6l12 12"
                        />
                    ) : (
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    )}
                </svg>
            </button>

            {isMobileOpen && (
                <div
                    className="lg:hidden fixed inset-0 bg-gray-600 bg-opacity-75 z-40"
                    onClick={closeMobile}
                />
            )}

            <aside
                className={`
                    fixed top-0 left-0 z-40 h-screen transition-all duration-300 ease-in-out bg-white border-r border-gray-200
                    ${isCollapsed ? 'lg:w-20' : 'lg:w-64'}
                    ${isMobileOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'}
                `}
            >
                <div className="flex flex-col h-full">
                    <div className="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                        <Link
                            href={navRoutes.dashboard()}
                            className={
                                isCollapsed
                                    ? 'flex items-center justify-center w-full'
                                    : 'flex items-center'
                            }
                        >
                            <Logo
                                showName={!isCollapsed}
                                width={isCollapsed ? 32 : 48}
                                height={isCollapsed ? 32 : 48}
                            />
                        </Link>

                        <button
                            onClick={toggleCollapse}
                            className="hidden lg:block cursor-pointer p-1.5 rounded-md hover:bg-gray-100 text-gray-500"
                            aria-label={t('sidebar.actions.toggle_sidebar')}
                        >
                            <svg
                                className="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                {isCollapsed ? (
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M13 5l7 7-7 7M5 5l7 7-7 7"
                                    />
                                ) : (
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                                    />
                                )}
                            </svg>
                        </button>
                    </div>

                    <nav className="flex-1 px-3 py-4 space-y-1 custom-scrollbar overflow-y-auto">
                        {navGroups.map((group) => (
                            <div key={group.key}>
                                {group.label &&
                                    (isCollapsed ? (
                                        <div className="my-2 mx-1 border-t border-gray-200" />
                                    ) : (
                                        <p className="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                            {group.label}
                                        </p>
                                    ))}
                                {group.items.map((item) => {
                                    const active = isActive(item.href);
                                    return (
                                        <Tooltip
                                            key={item.href}
                                            content={item.name}
                                            position="right"
                                            disabled={!isCollapsed}
                                        >
                                            <Link
                                                href={item.href}
                                                className={`
                                                    flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors w-full
                                                    ${active ? 'bg-indigo-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'}
                                                    ${isCollapsed ? 'justify-center' : ''}
                                                `}
                                                onClick={closeMobile}
                                            >
                                                <NavIcon
                                                    type={item.icon}
                                                    className={`w-5 h-5 ${!isCollapsed && 'mr-3'}`}
                                                />
                                                {!isCollapsed && <span>{item.name}</span>}
                                            </Link>
                                        </Tooltip>
                                    );
                                })}
                            </div>
                        ))}
                    </nav>

                    <div className="border-t border-gray-200 bg-gray-50">
                        {isCollapsed ? (
                            <div className="p-2 space-y-2">
                                <Tooltip
                                    content={`${user.name} - ${t('sidebar.actions.profile')}`}
                                    position="right"
                                >
                                    <Link
                                        href={navRoutes.profile()}
                                        className={`
                                            relative flex items-center justify-center p-2 rounded-lg transition-all
                                            ${isActive(navRoutes.profile()) ? 'bg-indigo-50 ring-2 ring-indigo-600' : 'bg-white hover:bg-indigo-50'}
                                        `}
                                    >
                                        <UserAvatar name={user.name} size="sm" />
                                        {isActive(navRoutes.profile()) && (
                                            <span className="absolute top-1 right-1 w-2 h-2 bg-indigo-600 rounded-full" />
                                        )}
                                    </Link>
                                </Tooltip>

                                <Tooltip content={t('sidebar.actions.logout')} position="right">
                                    <Link
                                        href={navRoutes.logout()}
                                        method="post"
                                        as="button"
                                        className="w-full flex items-center justify-center p-2.5 text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                                    >
                                        <NavIcon type="logout" className="w-5 h-5" />
                                    </Link>
                                </Tooltip>
                            </div>
                        ) : (
                            <>
                                <Link
                                    href={navRoutes.profile()}
                                    className={`
                                        flex items-center space-x-3 p-3 transition-all mb-3
                                        ${
                                            isActive(navRoutes.profile())
                                                ? 'bg-indigo-50 border-b border-indigo-200'
                                                : 'bg-white border-b border-gray-200 hover:border-indigo-200 hover:bg-indigo-50'
                                        }
                                    `}
                                >
                                    <UserAvatar name={user.name} size="md" />
                                    <div className="flex-1 min-w-0">
                                        <p
                                            className={`text-sm font-semibold truncate ${isActive(navRoutes.profile()) ? 'text-indigo-900' : 'text-gray-900'}`}
                                        >
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

                                <div className="px-2 pb-3">
                                    <Link
                                        href={navRoutes.logout()}
                                        method="post"
                                        as="button"
                                        className="w-full flex items-center justify-center space-x-2 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                                    >
                                        <NavIcon type="logout" className="w-4 h-4" />
                                        <span>{t('sidebar.actions.logout')}</span>
                                    </Link>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </aside>
        </>
    );
};
