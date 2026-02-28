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
                        className={`fixed top-0 z-20 bg-white border-b border-gray-200 h-16 ${sidebarCollapsed ? 'w-[calc(100%-5rem)]' : 'w-[calc(100%-16rem)]'} transition-all duration-300`}
                    >
                        <div className="flex items-center justify-between h-full px-4 lg:px-8">
                            <div className="lg:hidden w-10"></div>
                            <div className="flex-1">
                                {breadcrumb ? (
                                    <Breadcrumb items={breadcrumb} />
                                ) : title ? (
                                    <h1 className="lg:text-lg font-semibold text-gray-900 hidden sm:block">
                                        {title}
                                    </h1>
                                ) : null}
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
                    <main className="px-4 pt-14 lg:pt-20 min-h-[calc(100vh-4rem)]">
                        <div className="max-w-340 mx-auto">{children}</div>
                    </main>
                </div>

                <FlashToastHandler flash={flash} />
            </div>
        </>
    );
};

export default AuthenticatedLayout;
