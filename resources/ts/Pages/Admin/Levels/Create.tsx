import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { LevelForm } from '@/Components/features/levels';

export default function CreateLevel() {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(() => ({
        createTitle: t('admin_pages.levels.create_title'),
        createSubtitle: t('admin_pages.levels.create_subtitle'),
    }), [t]);

    const handleCancel = () => {
        window.history.back();
    };

    return (
        <AuthenticatedLayout breadcrumb={breadcrumbs.levelCreate()}>
            <LevelForm
                title={translations.createTitle}
                subtitle={translations.createSubtitle}
                onCancel={handleCancel}
            />
        </AuthenticatedLayout>
    );
}

