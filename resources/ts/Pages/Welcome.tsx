import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    ChartBarIcon,
    ShieldCheckIcon,
    UserGroupIcon,
    ClockIcon,
    SparklesIcon,
    CheckCircleIcon,
    ArrowRightIcon
} from '@heroicons/react/24/outline';
import { useState, useEffect } from 'react';
import { trans } from '@/utils';
import { LogoExamena } from '@/Components/shared/LogoExamena';

const Welcome = () => {
    const { auth } = usePage<PageProps>().props;
    const [scrolled, setScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            const heroHeight = window.innerHeight * 0.8; // 80% de la hauteur de l'écran
            setScrolled(window.scrollY > heroHeight);
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const features = [
        {
            icon: ClipboardDocumentCheckIcon,
            title: trans('welcome.features.assessment_creation.title'),
            description: trans('welcome.features.assessment_creation.description'),
            color: "blue",
            benefits: [
                trans('welcome.features.assessment_creation.benefits.0'),
                trans('welcome.features.assessment_creation.benefits.1'),
                trans('welcome.features.assessment_creation.benefits.2')
            ]
        },
        {
            icon: UserGroupIcon,
            title: trans('welcome.features.group_management.title'),
            description: trans('welcome.features.group_management.description'),
            color: "purple",
            benefits: [
                trans('welcome.features.group_management.benefits.0'),
                trans('welcome.features.group_management.benefits.1'),
                trans('welcome.features.group_management.benefits.2')
            ]
        },
        {
            icon: ClockIcon,
            title: trans('welcome.features.timed_assessments.title'),
            description: trans('welcome.features.timed_assessments.description'),
            color: "green",
            benefits: [
                trans('welcome.features.timed_assessments.benefits.0'),
                trans('welcome.features.timed_assessments.benefits.1'),
                trans('welcome.features.timed_assessments.benefits.2')
            ]
        },
        {
            icon: ChartBarIcon,
            title: trans('welcome.features.statistics.title'),
            description: trans('welcome.features.statistics.description'),
            color: "yellow",
            benefits: [
                trans('welcome.features.statistics.benefits.0'),
                trans('welcome.features.statistics.benefits.1'),
                trans('welcome.features.statistics.benefits.2')
            ]
        },
        {
            icon: ShieldCheckIcon,
            title: trans('welcome.features.security.title'),
            description: trans('welcome.features.security.description'),
            color: "red",
            benefits: [
                trans('welcome.features.security.benefits.0'),
                trans('welcome.features.security.benefits.1'),
                trans('welcome.features.security.benefits.2')
            ]
        },
        {
            icon: AcademicCapIcon,
            title: trans('welcome.features.instant_correction.title'),
            description: trans('welcome.features.instant_correction.description'),
            color: "indigo",
            benefits: [
                trans('welcome.features.instant_correction.benefits.0'),
                trans('welcome.features.instant_correction.benefits.1'),
                trans('welcome.features.instant_correction.benefits.2')
            ]
        }
    ];

    const useCases = [
        {
            title: trans('welcome.use_cases.institutions.title'),
            icon: ShieldCheckIcon,
            features: [
                trans('welcome.use_cases.institutions.features.0'),
                trans('welcome.use_cases.institutions.features.1'),
                trans('welcome.use_cases.institutions.features.2'),
                trans('welcome.use_cases.institutions.features.3'),
                trans('welcome.use_cases.institutions.features.4')
            ],
            highlight: trans('welcome.use_cases.institutions.highlight')
        },
        {
            title: trans('welcome.use_cases.teachers.title'),
            icon: AcademicCapIcon,
            features: [
                trans('welcome.use_cases.teachers.features.0'),
                trans('welcome.use_cases.teachers.features.1'),
                trans('welcome.use_cases.teachers.features.2'),
                trans('welcome.use_cases.teachers.features.3'),
                trans('welcome.use_cases.teachers.features.4')
            ],
            highlight: trans('welcome.use_cases.teachers.highlight')
        },
        {
            title: trans('welcome.use_cases.students.title'),
            icon: UserGroupIcon,
            features: [
                trans('welcome.use_cases.students.features.0'),
                trans('welcome.use_cases.students.features.1'),
                trans('welcome.use_cases.students.features.2'),
                trans('welcome.use_cases.students.features.3'),
                trans('welcome.use_cases.students.features.4')
            ],
            highlight: trans('welcome.use_cases.students.highlight')
        }
    ];

    const testimonials = [
        {
            quote: trans('welcome.testimonials.items.0.quote'),
            author: trans('welcome.testimonials.items.0.author'),
            role: trans('welcome.testimonials.items.0.role'),
            rating: 5
        },
        {
            quote: trans('welcome.testimonials.items.1.quote'),
            author: trans('welcome.testimonials.items.1.author'),
            role: trans('welcome.testimonials.items.1.role'),
            rating: 5
        },
        {
            quote: trans('welcome.testimonials.items.2.quote'),
            author: trans('welcome.testimonials.items.2.author'),
            role: trans('welcome.testimonials.items.2.role'),
            rating: 5
        }
    ];

    const stats = [
        {
            label: trans('welcome.stats.active_students.label'),
            value: trans('welcome.stats.active_students.value'),
            description: trans('welcome.stats.active_students.description')
        },
        {
            label: trans('welcome.stats.assessments_created.label'),
            value: trans('welcome.stats.assessments_created.value'),
            description: trans('welcome.stats.assessments_created.description')
        },
        {
            label: trans('welcome.stats.satisfaction.label'),
            value: trans('welcome.stats.satisfaction.value'),
            description: trans('welcome.stats.satisfaction.description')
        },
        {
            label: trans('welcome.stats.time_saved.label'),
            value: trans('welcome.stats.time_saved.value'),
            description: trans('welcome.stats.time_saved.description')
        }
    ];

    const colorClasses: Record<string, string> = {
        blue: "bg-blue-100 text-blue-600",
        purple: "bg-purple-100 text-purple-600",
        green: "bg-green-100 text-green-600",
        yellow: "bg-yellow-100 text-yellow-600",
        red: "bg-red-100 text-red-600",
        indigo: "bg-indigo-100 text-indigo-600"
    };

    return (
        <div className="min-h-screen bg-linear-to-br from-gray-50 via-blue-50 to-indigo-50">
            <Head title={trans('welcome.page_title')} />

            {/* Navigation */}
            <nav className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${scrolled
                ? 'bg-white/95 backdrop-blur-lg shadow'
                : ' backdrop-blur-lg '
                }`}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center h-20">
                        <div className="flex items-center space-x-3">

                            <LogoExamena />
                            <span className="text-2xl font-bold bg-linear-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                {trans('welcome.app_name')}
                            </span>
                        </div>
                        <div className="flex items-center space-x-4">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex items-center gap-2 px-6 py-3 bg-linear-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200  transform hover:-translate-y-0.5"
                                >
                                    {trans('welcome.nav.dashboard')}
                                    <ArrowRightIcon className="w-4 h-4" />
                                </Link>
                            ) : (
                                <>
                                    <a
                                        href="#features"
                                        className="hidden md:inline-flex text-gray-700 hover:text-blue-600 font-medium transition-colors"
                                    >
                                        {trans('welcome.nav.features')}
                                    </a>
                                    <a
                                        href="#testimonials"
                                        className="hidden md:inline-flex text-gray-700 hover:text-blue-600 font-medium transition-colors"
                                    >
                                        {trans('welcome.nav.testimonials')}
                                    </a>
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center gap-2 px-6 py-3 bg-linear-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 transform hover:-translate-y-0.5"
                                    >
                                        {trans('welcome.nav.login')}
                                        <ArrowRightIcon className="w-4 h-4" />
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            {/* Hero Section */}
            <section className="relative pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
                {/* Decorative elements */}
                <div className="absolute top-0 left-0 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob"></div>
                <div className="absolute top-0 right-0 w-96 h-96 bg-indigo-400 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-2000"></div>
                <div className="absolute bottom-0 left-1/2 w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-4000"></div>

                <div className="max-w-7xl mx-auto relative">
                    <div className="text-center">
                        <div className="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-medium mb-8 animate-fade-in">
                            <SparklesIcon className="w-4 h-4" />
                            {trans('welcome.hero.badge')}
                        </div>
                        <h1 className="text-5xl sm:text-6xl lg:text-7xl font-extrabold text-gray-900 mb-6 animate-fade-in-up">
                            {trans('welcome.hero.title_part1')}<span className="bg-linear-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                {trans('welcome.hero.title_highlight')}
                            </span>
                            <br />
                            {trans('welcome.hero.title_part2')}
                        </h1>
                        <p className="mt-6 text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed animate-fade-in-up animation-delay-200">
                            {trans('welcome.hero.subtitle')}
                        </p>
                        <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center animate-fade-in-up animation-delay-400">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-linear-to-r from-blue-600 to-indigo-600 text-white text-lg font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow hover:shadow-xl transform hover:-translate-y-1"
                                >
                                    {trans('welcome.hero.cta_dashboard')}
                                    <ArrowRightIcon className="w-5 h-5" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-linear-to-r from-blue-600 to-indigo-600 text-white text-lg font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow hover:shadow-xl transform hover:-translate-y-1"
                                    >
                                        {trans('welcome.hero.cta_start')}
                                        <ArrowRightIcon className="w-5 h-5" />
                                    </Link>
                                </>
                            )}
                        </div>

                        {/* Stats */}
                        <div className="mt-20 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                            {stats.map((stat, index) => (
                                <div key={index} className="text-center animate-fade-in-up" style={{ animationDelay: `${600 + index * 100}ms` }}>
                                    <div className="text-4xl font-bold bg-linear-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                        {stat.value}
                                    </div>
                                    <div className="mt-2 text-sm font-semibold text-gray-700">{stat.label}</div>
                                    <div className="mt-1 text-xs text-gray-500">{stat.description}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* Features Section */}
            <section id="features" className="py-20 px-4 sm:px-6 lg:px-8 bg-white/50 backdrop-blur-sm scroll-mt-20">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-16">
                        <h2 className="text-base text-blue-600 font-semibold tracking-wide uppercase mb-3">
                            {trans('welcome.features.section_title')}
                        </h2>
                        <p className="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                            {trans('welcome.features.title')}
                        </p>
                        <p className="mt-4 text-xl text-gray-600 max-w-3xl mx-auto">
                            {trans('welcome.features.subtitle')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        {features.map((feature, index) => (
                            <div
                                key={index}
                                className="group relative bg-white p-8 rounded-2xl shadow hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 overflow-hidden"
                            >
                                <div className="absolute top-0 right-0 w-32 h-32 bg-linear-to-br from-blue-50 to-transparent rounded-bl-full opacity-50"></div>
                                <div className={`relative inline-flex p-4 rounded-xl ${colorClasses[feature.color]} mb-6 group-hover:scale-110 transition-transform duration-300`}>
                                    <feature.icon className="w-8 h-8" />
                                </div>
                                <h3 className="relative text-xl font-bold text-gray-900 mb-3">
                                    {feature.title}
                                </h3>
                                <p className="relative text-gray-600 leading-relaxed mb-4">
                                    {feature.description}
                                </p>
                                <ul className="relative space-y-2">
                                    {feature.benefits.map((benefit, idx) => (
                                        <li key={idx} className="flex items-center gap-2 text-sm text-gray-500">
                                            <CheckCircleIcon className="w-4 h-4 text-green-500 shrink-0" />
                                            {benefit}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Use Cases Section */}
            <section className="py-20 px-4 sm:px-6 lg:px-8 bg-linear-to-b from-gray-50 to-white">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-16">
                        <h2 className="text-base text-blue-600 font-semibold tracking-wide uppercase mb-3">
                            {trans('welcome.use_cases.section_title')}
                        </h2>
                        <p className="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                            {trans('welcome.use_cases.title')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {useCases.map((useCase, index) => (
                            <div
                                key={index}
                                className="relative bg-white p-8 rounded-2xl shadow-lg border-2 border-gray-100 hover:border-blue-300 transition-all duration-300"
                            >
                                <div className="absolute -top-4 left-8 px-4 py-1 bg-linear-to-r from-blue-600 to-indigo-600 text-white text-xs font-bold rounded-full shadow">
                                    {useCase.highlight}
                                </div>
                                <div className="inline-flex p-4 rounded-xl bg-blue-100 text-blue-600 mb-6">
                                    <useCase.icon className="w-8 h-8" />
                                </div>
                                <h3 className="text-2xl font-bold text-gray-900 mb-6">
                                    {useCase.title}
                                </h3>
                                <ul className="space-y-4">
                                    {useCase.features.map((item, idx) => (
                                        <li key={idx} className="flex items-start gap-3">
                                            <CheckCircleIcon className="w-6 h-6 text-green-500 shrink-0 mt-0.5" />
                                            <span className="text-gray-700">{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Testimonials Section */}
            <section id="testimonials" className="py-20 px-4 sm:px-6 lg:px-8 bg-white scroll-mt-20">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-16">
                        <h2 className="text-base text-blue-600 font-semibold tracking-wide uppercase mb-3">
                            {trans('welcome.testimonials.section_title')}
                        </h2>
                        <p className="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                            {trans('welcome.testimonials.title')}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {testimonials.map((testimonial, index) => (
                            <div
                                key={index}
                                className="bg-linear-to-br from-blue-50 to-white p-8 rounded-2xl shadow border border-blue-100"
                            >
                                <div className="flex gap-1 mb-4">
                                    {[...Array(testimonial.rating)].map((_, i) => (
                                        <svg key={i} className="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                        </svg>
                                    ))}
                                </div>
                                <p className="text-gray-700 italic mb-6 leading-relaxed">
                                    &ldquo;{testimonial.quote}&rdquo;
                                </p>
                                <div>
                                    <p className="font-bold text-gray-900">{testimonial.author}</p>
                                    <p className="text-sm text-gray-500">{testimonial.role}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-20 px-4 sm:px-6 lg:px-8 bg-linear-to-br from-blue-600 to-indigo-600">
                <div className="max-w-4xl mx-auto text-center">
                    <h2 className="text-4xl md:text-5xl font-extrabold text-white mb-6">
                        {trans('welcome.cta.title')}
                    </h2>
                    <p className="text-xl text-blue-100 mb-10 max-w-2xl mx-auto leading-relaxed">
                        {trans('welcome.cta.subtitle')}
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-flex items-center justify-center gap-2 px-10 py-5 bg-white text-blue-600 text-lg font-bold rounded-xl hover:bg-gray-50 transition-all duration-200 shadow hover:shadow-xl transform hover:-translate-y-1"
                            >
                                {trans('welcome.cta.button_dashboard')}
                                <ArrowRightIcon className="w-5 h-5" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="inline-flex items-center justify-center gap-2 px-10 py-5 bg-white text-blue-600 text-lg font-bold rounded-xl hover:bg-gray-50 transition-all duration-200 shadow hover:shadow-xl transform hover:-translate-y-1"
                                >
                                    {trans('welcome.cta.button_start')}
                                    <ArrowRightIcon className="w-5 h-5" />
                                </Link>
                            </>
                        )}
                    </div>
                    <p className="mt-8 text-blue-100 text-sm">
                        {trans('welcome.cta.footer_note')}
                    </p>
                </div>
            </section>            {/* Footer */}
            <footer className="bg-gray-900 text-gray-300 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                        <div>
                            <div className="flex items-center space-x-3 mb-4">
                                <LogoExamena width={32} height={32} />
                                <span className="text-xl font-bold text-white">{trans('welcome.app_name')}</span>
                            </div>
                            <p className="text-sm text-gray-400">
                                {trans('welcome.footer.description')}
                            </p>
                        </div>
                        <div>
                            <h3 className="text-white font-semibold mb-4">{trans('welcome.footer.product')}</h3>
                            <ul className="space-y-2 text-sm">
                                <li><a href="#" className="hover:text-white transition-colors">{trans('welcome.footer.links.features')}</a></li>
                                <li><a href="#" className="hover:text-white transition-colors">{trans('welcome.footer.links.pricing')}</a></li>
                                <li><a href="#" className="hover:text-white transition-colors">{trans('welcome.footer.links.documentation')}</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-white font-semibold mb-4">{trans('welcome.footer.resources')}</h3>
                            <ul className="space-y-2 text-sm">
                                <li><a href="https://github.com/Soule73/examena" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">{trans('welcome.footer.links.github')}</a></li>
                                <li><a href="https://github.com/Soule73/examena/wiki" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">{trans('welcome.footer.links.wiki')}</a></li>
                                <li><a href="https://github.com/Soule73/examena/issues" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">{trans('welcome.footer.links.support')}</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="text-white font-semibold mb-4">{trans('welcome.footer.contact')}</h3>
                            <ul className="space-y-2 text-sm">
                                <li><a href="mailto:sourtoumo@gmail.com" className="hover:text-white transition-colors">{trans('welcome.footer.links.email')}</a></li>
                                <li><a href="https://github.com/Soule73" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">@Soule73</a></li>
                                <li><a href="https://github.com/badressa" target="_blank" rel="noopener noreferrer" className="hover:text-white transition-colors">@badressa</a></li>
                            </ul>
                        </div>
                    </div>
                    <div className="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                        <p className="text-sm text-gray-400">
                            {trans('welcome.footer.copyright')} <a href="https://github.com/Soule73" target="_blank" rel="noopener noreferrer" className="text-blue-400 hover:text-blue-300">Soule73</a> & <a href="https://github.com/badressa" target="_blank" rel="noopener noreferrer" className="text-blue-400 hover:text-blue-300">badressa</a>
                        </p>
                        <div className="flex space-x-6 mt-4 md:mt-0">
                            <a href="https://github.com/Soule73/examena" target="_blank" rel="noopener noreferrer" className="text-gray-400 hover:text-white transition-colors">
                                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path fillRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clipRule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default Welcome;