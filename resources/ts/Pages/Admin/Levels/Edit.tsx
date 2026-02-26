import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { type Level } from '@/types';
import { LevelForm } from '@/Components/features/levels';

interface Props {
    level: Level;
}

export default function EditLevel({ level }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            edit: t('admin_pages.levels.edit'),
            editTitle: t('admin_pages.levels.edit_title'),
            editSubtitle: t('admin_pages.levels.edit_subtitle'),
        }),
        [t],
    );

    const handleCancel = () => {
        router.visit(route('admin.levels.index'));
    };

    return (
        <AuthenticatedLayout
            title={translations.edit}
            breadcrumb={breadcrumbs.levelEdit(level.name)}
        >
            <LevelForm
                title={translations.editTitle}
                subtitle={translations.editSubtitle}
                level={level}
                onCancel={handleCancel}
            />
        </AuthenticatedLayout>
    );
}
