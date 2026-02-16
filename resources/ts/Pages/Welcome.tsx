import { GuestLayout } from '@/Components/layout/GuestLayout';
import { Logo } from '@/Components';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Link, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useCallback, useState } from 'react';

interface WelcomeProps {
  locale: string;
}

interface FeatureCardProps {
  icon: React.ReactNode;
  title: string;
  description: string;
}

const FeatureCard = ({ icon, title, description }: FeatureCardProps) => (
  <div className="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg hover:border-indigo-200 transition-all duration-300">
    <div className="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center text-indigo-600 mb-4">
      {icon}
    </div>
    <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
    <p className="text-gray-600 text-sm leading-relaxed">{description}</p>
  </div>
);

interface StatItemProps {
  value: string;
  label: string;
}

const StatItem = ({ value, label }: StatItemProps) => (
  <div className="text-center">
    <div className="text-3xl font-bold text-indigo-600">{value}</div>
    <div className="text-sm text-gray-500 mt-1">{label}</div>
  </div>
);

const Welcome = ({ locale }: WelcomeProps) => {
  const { t } = useTranslations();
  const [isChangingLocale, setIsChangingLocale] = useState(false);

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

  const features = [
    {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      ),
      title: t('landing.features.assessments.title'),
      description: t('landing.features.assessments.description'),
    },
    {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      ),
      title: t('landing.features.security.title'),
      description: t('landing.features.security.description'),
    },
    {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      ),
      title: t('landing.features.analytics.title'),
      description: t('landing.features.analytics.description'),
    },
    {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      ),
      title: t('landing.features.academic.title'),
      description: t('landing.features.academic.description'),
    },
    {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      ),
      title: t('landing.features.roles.title'),
      description: t('landing.features.roles.description'),
    },
    {
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
      ),
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
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      ),
    },
    {
      role: t('landing.roles.teacher.role'),
      description: t('landing.roles.teacher.description'),
      color: 'bg-blue-50 border-blue-200 text-blue-700',
      iconColor: 'text-blue-600',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path d="M12 14l9-5-9-5-9 5 9 5z" />
          <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
          <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
        </svg>
      ),
    },
    {
      role: t('landing.roles.student.role'),
      description: t('landing.roles.student.description'),
      color: 'bg-green-50 border-green-200 text-green-700',
      iconColor: 'text-green-600',
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      ),
    },
  ];

  return (
    <GuestLayout title="Evalium">
      {/* Navigation */}
      <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
        <Logo showName showIcon width={40} height={40} />
        <div className="flex items-center gap-3">
          <button
            onClick={toggleLocale}
            disabled={isChangingLocale}
            className="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors duration-200 disabled:opacity-50"
            aria-label={t('landing.nav.switch_language')}
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
            </svg>
            {locale === 'fr' ? 'EN' : 'FR'}
          </button>
          <Link
            href={route('login')}
            className="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200"
          >
            {t('landing.nav.login')}
          </Link>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div className="text-center max-w-3xl mx-auto">
          <div className="flex justify-center mb-8">
            <Logo showIcon width={72} height={72} />
          </div>
          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight">
            {t('landing.hero.title_start')}{' '}
            <span className="text-indigo-600">{t('landing.hero.title_highlight')}</span>
          </h1>
          <p className="mt-6 text-lg sm:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
            {t('landing.hero.subtitle')}
          </p>
          <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href={route('login')}
              className="inline-flex items-center justify-center px-8 py-3.5 bg-indigo-600 text-white text-base font-semibold rounded-lg hover:bg-indigo-700 transition-colors duration-200 shadow-sm"
            >
              {t('landing.hero.cta_primary')}
              <svg xmlns="http://www.w3.org/2000/svg" className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </Link>
            <a
              href="#features"
              className="inline-flex items-center justify-center px-8 py-3.5 bg-white text-indigo-600 text-base font-semibold rounded-lg border border-indigo-200 hover:bg-indigo-50 transition-colors duration-200"
            >
              {t('landing.hero.cta_secondary')}
            </a>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="bg-white rounded-2xl border border-gray-200 p-8 sm:p-12">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            <StatItem value="3" label={t('landing.stats.question_types')} />
            <StatItem value="2" label={t('landing.stats.delivery_modes')} />
            <StatItem value="3" label={t('landing.stats.user_roles')} />
            <StatItem value="2" label={t('landing.stats.languages')} />
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div className="text-center mb-16">
          <h2 className="text-3xl sm:text-4xl font-bold text-gray-900">
            {t('landing.features.title')}
          </h2>
          <p className="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
            {t('landing.features.subtitle')}
          </p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {features.map((feature, index) => (
            <FeatureCard key={index} {...feature} />
          ))}
        </div>
      </section>

      {/* Roles Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div className="text-center mb-16">
          <h2 className="text-3xl sm:text-4xl font-bold text-gray-900">
            {t('landing.roles.title')}
          </h2>
          <p className="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
            {t('landing.roles.subtitle')}
          </p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {roleCards.map((card, index) => (
            <div
              key={index}
              className={`rounded-xl border p-8 text-center ${card.color} transition-all duration-300 hover:shadow-lg`}
            >
              <div className={`flex justify-center mb-4 ${card.iconColor}`}>
                {card.icon}
              </div>
              <h3 className="text-xl font-bold mb-3">{card.role}</h3>
              <p className="text-sm opacity-80 leading-relaxed">{card.description}</p>
            </div>
          ))}
        </div>
      </section>

      {/* CTA Section */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div className="bg-indigo-600 rounded-2xl p-8 sm:p-16 text-center">
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
            {t('landing.cta.title')}
          </h2>
          <p className="text-indigo-100 text-lg max-w-2xl mx-auto mb-8">
            {t('landing.cta.subtitle')}
          </p>
          <Link
            href={route('login')}
            className="inline-flex items-center justify-center px-8 py-3.5 bg-white text-indigo-600 text-base font-semibold rounded-lg hover:bg-indigo-50 transition-colors duration-200"
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
            {t('landing.footer.copyright', { year: new Date().getFullYear().toString() })}
          </p>
        </div>
      </footer>
    </GuestLayout>
  );
};

export default Welcome;
