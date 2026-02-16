import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassModel, type Level } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { ClassForm } from '@/Components/features/classes';
import { route } from 'ziggy-js';

interface Props {
    class: ClassModel;
    levels: Level[];
}

export default function ClassEdit({ class: classItem, levels }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            editTitle: t('admin_pages.classes.edit_title'),
            editSubtitle: t('admin_pages.classes.edit_subtitle'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.editTitle}
            breadcrumb={breadcrumbs.admin.editClass(classItem)}
        >
            <ClassForm
                title={translations.editTitle}
                subtitle={translations.editSubtitle}
                classItem={classItem}
                levels={levels}
                onCancel={() => router.visit(route('admin.classes.show', classItem.id))}
            />
        </AuthenticatedLayout>
    );
}
