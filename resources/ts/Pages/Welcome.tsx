import { Logo } from '@/Components';
import { useTranslations } from '@/hooks/shared/useTranslations';
import {
    useScrollReveal,
    useStaggeredReveal,
    useScrollSlideshow,
} from '@/hooks/shared/useScrollReveal';
import { Head, Link, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useCallback, useState, useRef } from 'react';
import {
    DocumentTextIcon,
    LockClosedIcon,
    ChartBarIcon,
    AcademicCapIcon,
    UserGroupIcon,
    LanguageIcon,
    Cog6ToothIcon,
    UserIcon,
    ArrowRightIcon,
    PlayIcon,
    GlobeAltIcon,
} from '@heroicons/react/24/outline';

interface WelcomeProps {
    locale: string;
}

interface FeatureCardProps {
    icon: React.ReactNode;
    title: string;
    description: string;
    className?: string;
    style?: React.CSSProperties;
}

/**
 * Feature card component with icon, title and description.
 */
const FeatureCard = ({ icon, title, description, className = '', style }: FeatureCardProps) => (
    <div
        className={`bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-indigo-200 transition-all duration-300 group ${className}`}
        style={style}
    >
        <div className="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 mb-4 group-hover:bg-indigo-100 transition-colors">
            {icon}
        </div>
        <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
        <p className="text-gray-600 text-sm leading-relaxed">{description}</p>
    </div>
);

interface StatItemProps {
    value: string;
    label: string;
    className?: string;
    style?: React.CSSProperties;
}

/**
 * Stat counter item with value and label.
 */
const StatItem = ({ value, label, className = '', style }: StatItemProps) => (
    <div className={`text-center ${className}`} style={style}>
        <div className="text-3xl font-bold text-indigo-600">{value}</div>
        <div className="text-sm text-gray-500 mt-1">{label}</div>
    </div>
);

interface BrowserFrameProps {
    src: string;
    alt: string;
    className?: string;
}

/**
 * Browser-like frame wrapper for screenshots.
 */
const BrowserFrame = ({ src, alt, className = '' }: BrowserFrameProps) => (
    <div
        className={`rounded-xl border border-gray-200 bg-white shadow-2xl overflow-hidden ${className}`}
    >
        <div className="flex items-center gap-2 px-4 py-3 bg-gray-100 border-b border-gray-200">
            <div className="flex gap-1.5">
                <div className="w-3 h-3 rounded-full bg-red-400" />
                <div className="w-3 h-3 rounded-full bg-yellow-400" />
                <div className="w-3 h-3 rounded-full bg-green-400" />
            </div>
            <div className="flex-1 mx-4">
                <div className="h-5 bg-white rounded-md border border-gray-200 px-3 flex items-center">
                    <span className="text-xs text-gray-400 truncate">evalium.app</span>
                </div>
            </div>
        </div>
        <img src={src} alt={alt} className="w-full h-auto" loading="lazy" />
    </div>
);

interface ShowcaseSlide {
    src: string;
    alt: string;
    title: string;
    description: string;
}

interface StickyShowcaseProps {
    slides: ShowcaseSlide[];
    sectionTitle: string;
    sectionSubtitle: string;
}

const NAV_HEIGHT = 65;

/**
 * Sticky scroll slideshow section. The container stays pinned in the viewport
 * while the user scrolls. Each scroll step transitions to the next screenshot
 * with a smooth crossfade, similar to GitHub's landing page.
 */
const StickyShowcase = ({ slides, sectionTitle, sectionSubtitle }: StickyShowcaseProps) => {
    const { wrapperRef, activeIndex } = useScrollSlideshow(slides.length, NAV_HEIGHT);

    return (
        <div
            ref={wrapperRef}
            className="relative"
            style={{ height: `${(slides.length + 1) * 100}vh` }}
        >
            <div
                className="sticky z-10 flex flex-col justify-center"
                style={{
                    top: `${NAV_HEIGHT}px`,
                    height: `calc(100vh - ${NAV_HEIGHT}px)`,
                }}
            >
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <div className="text-center mb-6 md:mb-10">
                        <h2 className="text-3xl sm:text-4xl font-bold text-gray-900">
                            {sectionTitle}
                        </h2>
                        <p className="mt-3 text-lg text-gray-600 max-w-2xl mx-auto">
                            {sectionSubtitle}
                        </p>
                    </div>

                    <div className="flex flex-col md:flex-row items-center gap-6 md:gap-12 lg:gap-16">
                        <div className="md:w-2/5 order-2 md:order-1">
                            <div className="relative" style={{ minHeight: '160px' }}>
                                {slides.map((slide, index) => (
                                    <div
                                        key={index}
                                        className="transition-all ease-out"
                                        style={{
                                            transitionDuration: '500ms',
                                            opacity: index === activeIndex ? 1 : 0,
                                            transform:
                                                index === activeIndex
                                                    ? 'translateY(0)'
                                                    : index < activeIndex
                                                      ? 'translateY(-24px)'
                                                      : 'translateY(24px)',
                                            position:
                                                index === activeIndex ? 'relative' : 'absolute',
                                            inset: index === activeIndex ? undefined : 0,
                                            pointerEvents: index === activeIndex ? 'auto' : 'none',
                                        }}
                                    >
                                        <h3 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                                            {slide.title}
                                        </h3>
                                        <p className="text-lg text-gray-600 leading-relaxed">
                                            {slide.description}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            <div className="flex gap-2 mt-8">
                                {slides.map((_, index) => (
                                    <div
                                        key={index}
                                        className="h-1.5 rounded-full transition-all"
                                        style={{
                                            transitionDuration: '400ms',
                                            width: index === activeIndex ? '32px' : '16px',
                                            backgroundColor:
                                                index === activeIndex
                                                    ? '#4f46e5'
                                                    : index < activeIndex
                                                      ? '#a5b4fc'
                                                      : '#d1d5db',
                                        }}
                                    />
                                ))}
                            </div>
                        </div>

                        <div className="md:w-3/5 w-full order-1 md:order-2">
                            <div className="relative">
                                {slides.map((slide, index) => (
                                    <div
                                        key={index}
                                        className="transition-all ease-out"
                                        style={{
                                            transitionDuration: '600ms',
                                            opacity: index === activeIndex ? 1 : 0,
                                            transform:
                                                index === activeIndex ? 'scale(1)' : 'scale(0.95)',
                                            position:
                                                index === activeIndex ? 'relative' : 'absolute',
                                            inset: index === activeIndex ? undefined : 0,
                                            pointerEvents: index === activeIndex ? 'auto' : 'none',
                                        }}
                                    >
                                        <BrowserFrame src={slide.src} alt={slide.alt} />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-center mt-4">
                        <div className="flex items-center gap-2 text-sm text-gray-400">
                            <svg
                                className="w-4 h-4 animate-bounce"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M19 14l-7 7m0 0l-7-7m7 7V3"
                                />
                            </svg>
                            <span>
                                {activeIndex + 1} / {slides.length}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

/**
 * Landing page for the Evalium platform with scroll-triggered reveal animations.
 */
const Welcome = ({ locale }: WelcomeProps) => {
    const { t } = useTranslations();
    const [isChangingLocale, setIsChangingLocale] = useState(false);
    const videoRef = useRef<HTMLVideoElement>(null);
    const [isVideoPlaying, setIsVideoPlaying] = useState(false);

    const heroReveal = useScrollReveal({ animation: 'fade-up', threshold: 0.1 });
    const heroScreenshotReveal = useScrollReveal({
        animation: 'scale-up',
        threshold: 0.1,
        delay: 300,
    });
    const statsReveal = useStaggeredReveal(4, { staggerDelay: 100, animation: 'fade-up' });
    const videoReveal = useScrollReveal({ animation: 'fade-up', threshold: 0.1 });
    const videoPlayerReveal = useScrollReveal({
        animation: 'scale-up',
        threshold: 0.1,
        delay: 200,
    });
    const featuresHeaderReveal = useScrollReveal({ animation: 'fade-up' });
    const featuresGridReveal = useStaggeredReveal(6, { staggerDelay: 100, animation: 'fade-up' });
    const rolesHeaderReveal = useScrollReveal({ animation: 'fade-up' });
    const rolesReveal = useStaggeredReveal(3, { staggerDelay: 150, animation: 'fade-up' });
    const ctaReveal = useScrollReveal({ animation: 'scale-up' });

    const toggleLocale = useCallback(() => {
        const newLocale = locale === 'fr' ? 'en' : 'fr';
        setIsChangingLocale(true);
        router.post(
            route('locale.update'),
            { locale: newLocale },
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsChangingLocale(false);
                    window.location.reload();
                },
            },
        );
    }, [locale]);

    const handlePlayVideo = useCallback(() => {
        if (videoRef.current) {
            videoRef.current.play();
            setIsVideoPlaying(true);
        }
    }, []);

    const handleVideoPause = useCallback(() => {
        setIsVideoPlaying(false);
    }, []);

    const features = [
        {
            icon: <DocumentTextIcon className="w-6 h-6" />,
            title: t('landing.features.assessments.title'),
            description: t('landing.features.assessments.description'),
        },
        {
            icon: <LockClosedIcon className="w-6 h-6" />,
            title: t('landing.features.security.title'),
            description: t('landing.features.security.description'),
        },
        {
            icon: <ChartBarIcon className="w-6 h-6" />,
            title: t('landing.features.analytics.title'),
            description: t('landing.features.analytics.description'),
        },
        {
            icon: <AcademicCapIcon className="w-6 h-6" />,
            title: t('landing.features.academic.title'),
            description: t('landing.features.academic.description'),
        },
        {
            icon: <UserGroupIcon className="w-6 h-6" />,
            title: t('landing.features.roles.title'),
            description: t('landing.features.roles.description'),
        },
        {
            icon: <LanguageIcon className="w-6 h-6" />,
            title: t('landing.features.i18n.title'),
            description: t('landing.features.i18n.description'),
        },
    ];

    const roleCards = [
        {
            role: t('landing.roles.admin.role'),
            description: t('landing.roles.admin.description'),
            color: 'bg-purple-50 border-purple-200 text-purple-700',
            iconColor: 'text-purple-600',
            icon: <Cog6ToothIcon className="w-8 h-8" />,
        },
        {
            role: t('landing.roles.teacher.role'),
            description: t('landing.roles.teacher.description'),
            color: 'bg-blue-50 border-blue-200 text-blue-700',
            iconColor: 'text-blue-600',
            icon: <AcademicCapIcon className="w-8 h-8" />,
        },
        {
            role: t('landing.roles.student.role'),
            description: t('landing.roles.student.description'),
            color: 'bg-green-50 border-green-200 text-green-700',
            iconColor: 'text-green-600',
            icon: <UserIcon className="w-8 h-8" />,
        },
    ];

    const showcaseItems = [
        {
            src: '/images/landing/screenshots/create-assessment.png',
            alt: 'Create assessment interface',
            title: t('landing.showcase.create_assessment.title'),
            description: t('landing.showcase.create_assessment.description'),
        },
        {
            src: '/images/landing/screenshots/student-take-assessment.png',
            alt: 'Student taking assessment',
            title: t('landing.showcase.student_take.title'),
            description: t('landing.showcase.student_take.description'),
        },
        {
            src: '/images/landing/screenshots/class-results.png',
            alt: 'Class results dashboard',
            title: t('landing.showcase.class_results.title'),
            description: t('landing.showcase.class_results.description'),
        },
        {
            src: '/images/landing/screenshots/dashboard-admin.png',
            alt: 'Admin dashboard',
            title: t('landing.showcase.admin_dashboard.title'),
            description: t('landing.showcase.admin_dashboard.description'),
        },
        {
            src: '/images/landing/screenshots/student-result.png',
            alt: 'Student result view',
            title: t('landing.showcase.student_result.title'),
            description: t('landing.showcase.student_result.description'),
        },
        {
            src: '/images/landing/screenshots/admin-enrollements.png',
            alt: 'Enrollment management',
            title: t('landing.showcase.enrollments.title'),
            description: t('landing.showcase.enrollments.description'),
        },
    ];

    return (
        <>
            <Head title="Evalium" />

            <div className="min-h-screen bg-white">
                {/* Navigation */}
                <nav className="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                        <Logo showName showIcon width={40} height={40} />
                        <div className="flex items-center gap-3">
                            <button
                                onClick={toggleLocale}
                                disabled={isChangingLocale}
                                className="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors duration-200 disabled:opacity-50"
                                aria-label={t('landing.nav.switch_language')}
                            >
                                <GlobeAltIcon className="w-4 h-4" />
                                {locale === 'fr' ? 'EN' : 'FR'}
                            </button>
                            <Link
                                href={route('login')}
                                className="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200"
                            >
                                {t('landing.nav.login')}
                            </Link>
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <section className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 sm:pt-24 pb-12">
                    <div className="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
                        <div className="absolute top-20 -left-32 w-72 h-72 bg-indigo-100 rounded-full opacity-40 blur-3xl animate-float-slow" />
                        <div className="absolute top-40 -right-32 w-96 h-96 bg-purple-100 rounded-full opacity-30 blur-3xl animate-float-slow-reverse" />
                    </div>

                    <div
                        ref={heroReveal.ref}
                        className={`text-center max-w-3xl mx-auto ${heroReveal.className}`}
                    >
                        <div className="flex justify-center mb-8">
                            <Logo showIcon width={72} height={72} />
                        </div>
                        <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight">
                            {t('landing.hero.title_start')}{' '}
                            <span className="text-transparent bg-clip-text bg-linear-to-r from-indigo-600 to-purple-600">
                                {t('landing.hero.title_highlight')}
                            </span>
                        </h1>
                        <p className="mt-6 text-lg sm:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
                            {t('landing.hero.subtitle')}
                        </p>
                        <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                            <Link
                                href={route('login')}
                                className="inline-flex items-center justify-center px-8 py-3.5 bg-indigo-600 text-white text-base font-semibold rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-lg shadow-indigo-200"
                            >
                                {t('landing.hero.cta_primary')}
                                <ArrowRightIcon className="ml-2 w-5 h-5" />
                            </Link>
                            <a
                                href="#features"
                                className="inline-flex items-center justify-center px-8 py-3.5 bg-white text-indigo-600 text-base font-semibold rounded-lg border border-indigo-200 hover:bg-indigo-50 transition-colors duration-200"
                            >
                                {t('landing.hero.cta_secondary')}
                            </a>
                        </div>
                    </div>

                    <div
                        ref={heroScreenshotReveal.ref}
                        className={`mt-16 sm:mt-20 max-w-5xl mx-auto ${heroScreenshotReveal.className}`}
                    >
                        <BrowserFrame
                            src="/images/landing/hero/dashboard-teacher.png"
                            alt="Evalium teacher dashboard"
                        />
                    </div>
                </section>

                {/* Stats Section */}
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div
                        ref={statsReveal.containerRef}
                        className="bg-gray-50 rounded-2xl border border-gray-200 p-8 sm:p-12"
                    >
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
                            <StatItem
                                value="3"
                                label={t('landing.stats.question_types')}
                                {...statsReveal.getItemProps(0)}
                            />
                            <StatItem
                                value="2"
                                label={t('landing.stats.delivery_modes')}
                                {...statsReveal.getItemProps(1)}
                            />
                            <StatItem
                                value="3"
                                label={t('landing.stats.user_roles')}
                                {...statsReveal.getItemProps(2)}
                            />
                            <StatItem
                                value="2"
                                label={t('landing.stats.languages')}
                                {...statsReveal.getItemProps(3)}
                            />
                        </div>
                    </div>
                </section>

                {/* Video Demo Section */}
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                    <div
                        ref={videoReveal.ref}
                        className={`text-center mb-12 ${videoReveal.className}`}
                    >
                        <h2 className="text-3xl sm:text-4xl font-bold text-gray-900">
                            {t('landing.video_demo.title')}
                        </h2>
                        <p className="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                            {t('landing.video_demo.subtitle')}
                        </p>
                    </div>
                    <div
                        ref={videoPlayerReveal.ref}
                        className={`max-w-4xl mx-auto ${videoPlayerReveal.className}`}
                    >
                        <div className="relative rounded-2xl overflow-hidden shadow-2xl border border-gray-200 bg-black aspect-video">
                            <video
                                ref={videoRef}
                                className="w-full h-full object-contain"
                                poster="/images/landing/video/demo-poster.png"
                                controls={isVideoPlaying}
                                onPause={handleVideoPause}
                                onEnded={handleVideoPause}
                                onPlay={() => setIsVideoPlaying(true)}
                                preload="metadata"
                            >
                                <source
                                    src="/images/landing/video/teacher_student.mp4"
                                    type="video/mp4"
                                />
                            </video>
                            {!isVideoPlaying && (
                                <button
                                    onClick={handlePlayVideo}
                                    className="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition-colors duration-200 group cursor-pointer"
                                    aria-label={t('landing.video_demo.play_button')}
                                >
                                    <div className="w-20 h-20 rounded-full bg-white/90 group-hover:bg-white flex items-center justify-center shadow-lg transition-all duration-200 group-hover:scale-110">
                                        <PlayIcon className="w-8 h-8 text-indigo-600 ml-1" />
                                    </div>
                                </button>
                            )}
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section
                    id="features"
                    className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24"
                >
                    <div
                        ref={featuresHeaderReveal.ref}
                        className={`text-center mb-16 ${featuresHeaderReveal.className}`}
                    >
                        <h2 className="text-3xl sm:text-4xl font-bold text-gray-900">
                            {t('landing.features.title')}
                        </h2>
                        <p className="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                            {t('landing.features.subtitle')}
                        </p>
                    </div>
                    <div
                        ref={featuresGridReveal.containerRef}
                        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
                    >
                        {features.map((feature, index) => (
                            <FeatureCard
                                key={index}
                                {...feature}
                                {...featuresGridReveal.getItemProps(index)}
                            />
                        ))}
                    </div>
                </section>

                {/* Screenshots Showcase - Sticky scroll slideshow */}
                <section className="bg-linear-to-b from-gray-50 via-white to-gray-50">
                    <StickyShowcase
                        slides={showcaseItems}
                        sectionTitle={t('landing.screenshots.title')}
                        sectionSubtitle={t('landing.screenshots.subtitle')}
                    />
                </section>

                {/* Roles Section */}
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                    <div
                        ref={rolesHeaderReveal.ref}
                        className={`text-center mb-16 ${rolesHeaderReveal.className}`}
                    >
                        <h2 className="text-3xl sm:text-4xl font-bold text-gray-900">
                            {t('landing.roles.title')}
                        </h2>
                        <p className="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                            {t('landing.roles.subtitle')}
                        </p>
                    </div>
                    <div
                        ref={rolesReveal.containerRef}
                        className="grid grid-cols-1 md:grid-cols-3 gap-8"
                    >
                        {roleCards.map((card, index) => {
                            const itemProps = rolesReveal.getItemProps(index);
                            return (
                                <div
                                    key={index}
                                    className={`rounded-xl border p-8 text-center ${card.color} transition-all duration-300 hover:shadow-lg ${itemProps.className}`}
                                    style={itemProps.style}
                                >
                                    <div className={`flex justify-center mb-4 ${card.iconColor}`}>
                                        {card.icon}
                                    </div>
                                    <h3 className="text-xl font-bold mb-3">{card.role}</h3>
                                    <p className="text-sm opacity-80 leading-relaxed">
                                        {card.description}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* CTA Section */}
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                    <div
                        ref={ctaReveal.ref}
                        className={`bg-linear-to-br from-indigo-600 to-purple-700 rounded-2xl p-8 sm:p-16 text-center ${ctaReveal.className}`}
                    >
                        <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
                            {t('landing.cta.title')}
                        </h2>
                        <p className="text-indigo-100 text-lg max-w-2xl mx-auto mb-8">
                            {t('landing.cta.subtitle')}
                        </p>
                        <Link
                            href={route('login')}
                            className="inline-flex items-center justify-center px-8 py-3.5 bg-white text-indigo-600 text-base font-semibold rounded-lg hover:bg-indigo-50 transition-colors duration-200 shadow-lg"
                        >
                            {t('landing.cta.button')}
                        </Link>
                    </div>
                </section>

                {/* Footer */}
                <footer className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 border-t border-gray-200">
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <Logo showName showIcon width={32} height={32} />
                        <p className="text-sm text-gray-500">
                            {t('landing.footer.copyright', {
                                year: new Date().getFullYear().toString(),
                            })}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
};

export default Welcome;
