import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Subject, type Level, type PageProps } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { SubjectList } from '@/Components/shared/lists';

interface Props extends PageProps {
    subjects: PaginationType<Subject>;
    levels?: Level[];
    filters?: Record<string, string>;
}

export default function AdminSubjectsIndex({ subjects, levels, auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canCreate = hasPermission(auth.permissions, 'create subjects');

    const translations = useMemo(
        () => ({
            title: t('admin_pages.subjects.title'),
            subtitle: t('admin_pages.subjects.subtitle'),
            create: t('admin_pages.subjects.create'),
        }),
        [t],
    );

    const handleCreate = () => {
        router.visit(route('admin.subjects.create'));
    };

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.admin.subjects()}>
            <Section
                variant="flat"
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
                <SubjectList data={subjects} variant="admin" levels={levels ?? []} />
            </Section>
        </AuthenticatedLayout>
    );
}
