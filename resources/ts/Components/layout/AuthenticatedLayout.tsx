import { Head, usePage } from '@inertiajs/react';
import { type PageProps } from '@/types';
import { useMemo, useState, useEffect } from 'react';
import { Sidebar, Breadcrumb, FlashToastHandler, UserMenu, NotificationBell } from '@/Components';
import { type BreadcrumbItem } from '@/Components/layout/Breadcrumb';
import { AcademicYearSelector } from './AcademicYearSelector';

interface AuthenticatedLayoutProps {
    children: React.ReactNode;
    title?: string;
    breadcrumb?: BreadcrumbItem[];
    headerActions?: React.ReactNode;
}

const AuthenticatedLayout = ({
    children,
    title,
    breadcrumb,
    headerActions,
}: AuthenticatedLayoutProps) => {
    const { auth, flash } = usePage<PageProps>().props;
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    const currentPath = useMemo(() => window.location.pathname, []);

    useEffect(() => {
        const checkCollapsed = () => {
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            setSidebarCollapsed(collapsed);
        };

        checkCollapsed();
        window.addEventListener('storage', checkCollapsed);

        window.addEventListener('sidebarToggle', checkCollapsed);

        return () => {
            window.removeEventListener('storage', checkCollapsed);
            window.removeEventListener('sidebarToggle', checkCollapsed);
        };
    }, []);

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gray-50/90">
                {/* Sidebar */}
                <Sidebar currentPath={currentPath} user={auth.user} />

                <div
                    className={`transition-all relative duration-300 ${sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'}`}
                >
                    {/* Header */}
                    <header
                        className={`fixed top-0 z-20 bg-white/95 backdrop-blur-sm border border-gray-100 h-16 w-full ${sidebarCollapsed ? 'lg:w-[calc(100%-5rem)]' : 'lg:w-[calc(100%-16rem)]'} transition-all duration-300`}
                    >
                        <div className="flex items-center justify-between h-full px-4 lg:px-8">
                            <button
                                onClick={() => window.dispatchEvent(new Event('sidebarMobileOpen'))}
                                className="lg:hidden p-2 -ml-2 rounded-md hover:bg-gray-50 text-gray-600"
                                aria-label="Menu"
                            >
                                <svg
                                    className="w-6 h-6"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                </svg>
                            </button>
                            <div className="flex-1 min-w-0 overflow-hidden">
                                {title && (
                                    <h1 className="lg:text-lg font-semibold text-gray-900 hidden sm:block truncate">
                                        {title}
                                    </h1>
                                )}
                            </div>

                            {/* Academic Year Selector + User Menu */}
                            <div className="hidden lg:flex items-center gap-4">
                                {headerActions && (
                                    <div className="flex items-center">{headerActions}</div>
                                )}
                                <AcademicYearSelector user={auth.user} />
                                <NotificationBell />
                                <UserMenu user={auth.user} />
                            </div>

                            {/* Mobile: Only Academic Year Selector */}
                            <div className="lg:hidden">
                                <AcademicYearSelector user={auth.user} />
                            </div>
                        </div>
                    </header>

                    {/* Main content */}
                    <main className="px-4 pt-18 lg:pt-20 min-h-[calc(100vh-4rem)]">
                        <div className="max-w-340 mx-auto">
                            {breadcrumb && (
                                <div className="py-3">
                                    <Breadcrumb items={breadcrumb} />
                                </div>
                            )}
                            {children}
                        </div>
                    </main>
                </div>

                <FlashToastHandler flash={flash} />
            </div>
        </>
    );
};

export default AuthenticatedLayout;
