import { useState } from 'react';
import { router } from '@inertiajs/react';
import { trans } from '@/utils/translations';
import { route } from 'ziggy-js';
import { Select } from '../ui';

interface LanguageSelectorProps {
    currentLocale: string;
}

export default function LanguageSelector({ currentLocale }: LanguageSelectorProps) {
    const [isChanging, setIsChanging] = useState(false);

    const languages = [
        { value: 'fr', label: 'Français' },
        { value: 'en', label: 'English' }
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
                }
            }
        );
    };

    return (
        <div className="w-full sm:w-64">
            <Select
                label={trans('auth_pages.profile.language_label')}
                value={currentLocale}
                onChange={handleLanguageChange}
                disabled={isChanging}
                options={languages}
            />
        </div>
    );
}
