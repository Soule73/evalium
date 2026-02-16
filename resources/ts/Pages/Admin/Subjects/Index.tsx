import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Subject, type PageProps, type Level } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { SubjectList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    subjects: PaginationType<Subject>;
    levels: Level[];
}

export default function SubjectIndex({ subjects, levels, auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canCreate = hasPermission(auth.permissions, 'create subjects');

    const handleCreate = () => {
        router.visit(route('admin.subjects.create'));
    };

    const translations = useMemo(
        () => ({
            title: t('admin_pages.subjects.title'),
            subtitle: t('admin_pages.subjects.subtitle'),
            create: t('admin_pages.subjects.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.admin.subjects()}>
            <Section
                title={translations.title}
                subtitle={translations.subtitle}
                actions={
                    canCreate && (
                        <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
                            {translations.create}
                        </Button>
                    )
                }
            >
                <SubjectList data={subjects} variant="admin" levels={levels} />
            </Section>
        </AuthenticatedLayout>
    );
}
