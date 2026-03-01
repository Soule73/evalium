import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassModel, type ClassSubject, type PageProps, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { ClassSubjectDetail } from '@/Components/features';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    class: ClassModel;
    classSubject: ClassSubject;
    history: PaginationType<ClassSubject>;
    teachers?: User[];
}

/**
 * Admin page for viewing a class-subject assignment in its class context.
 */
export default function ClassSubjectShow({
    class: classItem,
    classSubject,
    history,
    teachers = [],
    auth,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout
            title={t('admin_pages.class_subjects.show_title')}
            breadcrumb={breadcrumbs.admin.showClassesSubject(classItem, classSubject)}
        >
            <ClassSubjectDetail
                classSubject={classSubject}
                history={history}
                teachers={teachers}
                permissions={auth.permissions}
                onBack={() => router.visit(route('admin.classes.show', classItem.id))}
            />
        </AuthenticatedLayout>
    );
}
