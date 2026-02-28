import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassSubject, type PageProps, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { ClassSubjectDetail } from '@/Components/features';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    classSubject: ClassSubject;
    history: PaginationType<ClassSubject>;
    teachers?: User[];
}

export default function ClassSubjectShow({ classSubject, history, teachers = [], auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout
            title={t('admin_pages.class_subjects.show_title')}
            breadcrumb={breadcrumbs.admin.classSubjects()}
        >
            <ClassSubjectDetail
                classSubject={classSubject}
                history={history}
                teachers={teachers}
                permissions={auth.permissions}
                onBack={() => router.visit(route('admin.class-subjects.index'))}
            />
        </AuthenticatedLayout>
    );
}
