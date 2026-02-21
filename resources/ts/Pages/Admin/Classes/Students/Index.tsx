import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassModel, type Enrollment, type PageProps, type PaginationType } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { EnrollmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    class: ClassModel;
    enrollments: PaginationType<Enrollment>;
    filters: {
        search?: string;
        status?: string;
    };
}

/**
 * Admin page listing all students enrolled in a given class.
 */
export default function ClassStudents({ class: classItem, enrollments, auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canCreate = hasPermission(auth.permissions, 'create enrollments');

    const pageBreadcrumbs = useMemo(
        () => breadcrumbs.admin.classStudentsList(classItem),
        [breadcrumbs, classItem],
    );

    return (
        <AuthenticatedLayout
            title={t('admin_pages.classes.class_students')}
            breadcrumb={pageBreadcrumbs}
        >
            <Section
                title={t('admin_pages.classes.class_students')}
                subtitle={`${classItem?.name} (${classItem.level?.name}, ${classItem.level?.description})`}
                actions={
                    <div className="flex space-x-3">
                        <Button
                            size="sm"
                            variant="outline"
                            color="secondary"
                            onClick={() => router.visit(route('admin.classes.show', classItem.id))}
                        >
                            {t('commons/ui.back')}
                        </Button>
                        {canCreate && (
                            <Button
                                size="sm"
                                variant="solid"
                                color="primary"
                                onClick={() => router.visit(route('admin.enrollments.create'))}
                            >
                                {t('admin_pages.enrollments.create')}
                            </Button>
                        )}
                    </div>
                }
            >
                <EnrollmentList
                    data={enrollments}
                    variant="admin"
                    showClassColumn={false}
                    onView={(enrollment) =>
                        router.visit(
                            route('admin.classes.students.show', {
                                class: classItem.id,
                                enrollment: enrollment.id,
                            }),
                        )
                    }
                />
            </Section>
        </AuthenticatedLayout>
    );
}
