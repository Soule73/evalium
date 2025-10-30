import { Head, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useMemo, useState, useEffect } from 'react';
import { Sidebar } from '@/Components/Sidebar';
import FlashToastHandler from '@/Components/Toast/FlashToastHandler';
import { Breadcrumb, BreadcrumbItem } from '@/Components/Breadcrumb/Breadcrumb';

interface AuthenticatedLayoutProps {
    children: React.ReactNode;
    title?: string;
    breadcrumb?: BreadcrumbItem[];
}

const AuthenticatedLayout = ({ children, title, breadcrumb }: AuthenticatedLayoutProps) => {
    const { auth, flash, permissions } = usePage<PageProps>().props;
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    const isSuperAdmin = !!auth.user.roles?.some(role => role.name === 'super_admin');
    const isAdmin = !!auth.user.roles?.some(role => role.name === 'admin' || role.name === 'super_admin');
    const isTeacher = !!auth.user.roles?.some(role => role.name === 'teacher');
    const isStudent = !!auth.user.roles?.some(role => role.name === 'student');

    const currentPath = useMemo(() => window.location.pathname, []);

    // Écouter les changements de l'état collapsed depuis localStorage
    useEffect(() => {
        const checkCollapsed = () => {
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            setSidebarCollapsed(collapsed);
        };

        checkCollapsed();
        window.addEventListener('storage', checkCollapsed);

        // Custom event pour les changements dans la même fenêtre
        window.addEventListener('sidebarToggle', checkCollapsed);

        return () => {
            window.removeEventListener('storage', checkCollapsed);
            window.removeEventListener('sidebarToggle', checkCollapsed);
        };
    }, []);

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gray-50">
                {/* Sidebar */}
                <Sidebar
                    isAdmin={isAdmin}
                    isSuperAdmin={isSuperAdmin}
                    isTeacher={isTeacher}
                    isStudent={isStudent}
                    currentPath={currentPath}
                    permissions={permissions}
                    user={auth.user}
                />

                {/* Main container avec marge ajustée selon l'état de la sidebar */}
                <div
                    className={`transition-all duration-300 ${sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'}`}
                >
                    {/* Header */}
                    <header className="sticky top-0 z-2 bg-white border-b border-gray-200 h-16">
                        <div className="flex items-center justify-between h-full px-4 lg:px-8">
                            {/* Espace pour le bouton hamburger mobile (géré par Sidebar) */}
                            <div className="lg:hidden w-10"></div>

                            {/* Breadcrumb ou Titre */}
                            <div className="flex-1">
                                {breadcrumb ? (
                                    <Breadcrumb items={breadcrumb} />
                                ) : title ? (
                                    <h1 className="lg:text-lg font-semibold text-gray-900 hidden sm:block">{title}</h1>
                                ) : null}
                            </div>

                            {/* Espace vide à droite pour l'équilibre */}
                            <div className="w-10"></div>
                        </div>
                    </header>

                    {/* Main content */}
                    <main className="p-4 lg:p-8 min-h-[calc(100vh-4rem)]">
                        <div className="max-w-7xl mx-auto">
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