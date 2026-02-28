import { Head } from '@inertiajs/react';
import { Logo } from './Logo';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface GuestLayoutProps {
    children: React.ReactNode;
    title?: string;
}

/**
 * Guest layout with split-screen branding panel on desktop.
 */
const GuestLayout = ({ children, title }: GuestLayoutProps) => {
    const { t } = useTranslations();

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen flex bg-gray-50">
                <div className="hidden lg:flex lg:w-1/2 xl:w-5/12 bg-linear-to-br from-indigo-600 via-indigo-700 to-indigo-900 relative overflow-hidden">
                    <div className="absolute inset-0">
                        <svg
                            className="absolute top-0 right-0 w-96 h-96 text-white/5"
                            viewBox="0 0 200 200"
                            fill="currentColor"
                        >
                            <circle cx="100" cy="100" r="100" />
                        </svg>
                        <svg
                            className="absolute bottom-0 left-0 w-72 h-72 text-white/5"
                            viewBox="0 0 200 200"
                            fill="currentColor"
                        >
                            <polygon points="100,0 200,200 0,200" />
                        </svg>
                        <svg
                            className="absolute top-1/2 left-1/3 w-48 h-48 text-white/3"
                            viewBox="0 0 200 200"
                            fill="currentColor"
                        >
                            <rect width="200" height="200" rx="30" />
                        </svg>
                    </div>

                    <div className="relative z-10 flex flex-col items-center justify-center w-full px-12">
                        <Logo
                            showName={false}
                            width={80}
                            height={80}
                            className="[&_svg_path]:fill-white/90 [&_svg_circle]:fill-white/60"
                        />
                        <h1 className="mt-6 text-4xl font-bold text-white tracking-tight">
                            Evalium
                        </h1>
                        <p className="mt-3 text-lg text-indigo-200 text-center max-w-sm">
                            {t('guest_layout.tagline')}
                        </p>
                        <div className="mt-10 flex items-center gap-8 text-indigo-300 text-sm">
                            <div className="flex items-center gap-2">
                                <svg
                                    className="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={1.5}
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>{t('guest_layout.feature_secure')}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <svg
                                    className="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={1.5}
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                                <span>{t('guest_layout.feature_fast')}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-12 xl:px-20">
                    {children}
                </div>
            </div>
        </>
    );
};

export { GuestLayout };
