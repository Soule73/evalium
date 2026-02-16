import { useState } from 'react';
import { router } from '@inertiajs/react';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { route } from 'ziggy-js';
import { Select } from '../ui';

interface LanguageSelectorProps {
    currentLocale: string;
}

export function LanguageSelector({ currentLocale }: LanguageSelectorProps) {
    const [isChanging, setIsChanging] = useState(false);
    const { t } = useTranslations();

    const languages = [
        { value: 'fr', label: 'Français' },
        { value: 'en', label: 'English' },
    ];

    const handleLanguageChange = (value: string | number) => {
        setIsChanging(true);
        router.post(
            route('locale.update'),
            { locale: String(value) },
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsChanging(false);
                    // Reload the page to apply new translations
                    window.location.reload();
                },
            },
        );
    };

    const searchPlaceholder = t('components.select.search_placeholder');
    const noOptionFound = t('components.select.no_option_found');

    return (
        <div className="w-full sm:w-64">
            <Select
                noOptionFound={noOptionFound}
                searchPlaceholder={searchPlaceholder}
                label={t('auth_pages.profile.language_label')}
                value={currentLocale}
                onChange={handleLanguageChange}
                disabled={isChanging}
                options={languages}
            />
        </div>
    );
}
