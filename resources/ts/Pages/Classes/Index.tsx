import { useCallback, useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@evalium/utils/types/datatable';
import {
    type ClassModel,
    type ClassRouteContext,
    type Level,
    type PageProps,
} from '@evalium/utils/types';
import { hasPermission } from '@evalium/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';
import { ClassFormModal } from '@/Components/features/classes';

interface Props extends PageProps {
    classes: PaginationType<ClassModel>;
    levels: Level[];
    routeContext: ClassRouteContext;
}

export default function ClassIndex({ classes, levels, auth, routeContext }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const isAdmin = routeContext.role === 'admin';
    const canCreate = isAdmin && hasPermission(auth.permissions, 'create classes');

    const [formModal, setFormModal] = useState<{ isOpen: boolean; classItem: ClassModel | null }>({
        isOpen: false,
        classItem: null,
    });

    const handleCreate = () => {
        setFormModal({ isOpen: true, classItem: null });
    };

    const closeFormModal = useCallback(() => {
        setFormModal({ isOpen: false, classItem: null });
    }, []);

    const translations = useMemo(
        () => ({
            title: isAdmin ? t('admin_pages.classes.title') : t('teacher_class_pages.index.title'),
            subtitle: isAdmin
                ? t('admin_pages.classes.subtitle')
                : t('teacher_class_pages.index.section_subtitle', { count: classes.total }),
            create: t('admin_pages.classes.create'),
        }),
        [t, isAdmin, classes.total],
    );

    const breadcrumb = isAdmin ? breadcrumbs.admin.classes() : breadcrumbs.teacher.classes();

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumb}>
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
                <ClassList data={classes} variant={routeContext.role} levels={levels} />
            </Section>

            {canCreate && (
                <ClassFormModal
                    isOpen={formModal.isOpen}
                    onClose={closeFormModal}
                    classItem={formModal.classItem}
                    levels={levels}
                />
            )}
        </AuthenticatedLayout>
    );
}
