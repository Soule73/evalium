import { Head } from '@inertiajs/react';

interface GuestLayoutProps {
    children: React.ReactNode;
    title?: string;
}

const GuestLayout = ({ children, title }: GuestLayoutProps) => {
    return (
        <>
            <Head title={title} />

            <div className="min-h-screen px-4 pt-6 sm:pt-0 bg-gray-50">

                <div>
                    {children}
                </div>
            </div>
        </>
    );
};

export { GuestLayout };