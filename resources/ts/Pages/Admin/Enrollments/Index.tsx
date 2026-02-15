import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Enrollment, type ClassModel, type PageProps } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { EnrollmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    enrollments: PaginationType<Enrollment>;
    classes: ClassModel[];
    filters?: {
        search?: string;
        class_id?: string;
        status?: string;
    };
}

export default function EnrollmentIndex({ enrollments, classes, auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canCreate = hasPermission(auth.permissions, 'create enrollments');

    const handleCreate = () => {
        router.visit(route('admin.enrollments.create'));
    };

    const translations = useMemo(
        () => ({
            title: t('admin_pages.enrollments.title'),
            subtitle: t('admin_pages.enrollments.subtitle'),
            create: t('admin_pages.enrollments.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.admin.enrollments()}
        >
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
                <EnrollmentList data={enrollments} classes={classes} variant="admin" />
            </Section>
        </AuthenticatedLayout>
    );
}
