import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type ClassModel, type PageProps, type Level } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    classes: PaginationType<ClassModel>;
    levels: Level[];
    filters?: {
        search?: string;
        level_id?: string;
    };
}

export default function ClassIndex({ classes, levels, auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canCreate = hasPermission(auth.permissions, 'create classes');

    const handleCreate = () => {
        router.visit(route('admin.classes.create'));
    };

    const translations = useMemo(
        () => ({
            title: t('admin_pages.classes.title'),
            subtitle: t('admin_pages.classes.subtitle'),
            create: t('admin_pages.classes.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.admin.classes()}>
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
                <ClassList data={classes} variant="admin" levels={levels} />
            </Section>
        </AuthenticatedLayout>
    );
}
