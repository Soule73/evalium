import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import type { ClassModel, ClassRouteContext, Enrollment, PageProps, PaginationType } from '@/types';
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
    routeContext: ClassRouteContext;
}

/**
 * Shared page listing all students enrolled in a class.
 * Used by both admin and teacher contexts via routeContext.
 */
export default function ClassStudentsIndex({
    class: classItem,
    enrollments,
    auth,
    routeContext,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const isAdmin = routeContext.role === 'admin';
    const canCreate = isAdmin && hasPermission(auth.permissions, 'create enrollments');

    const pageBreadcrumbs = useMemo(
        () =>
            isAdmin
                ? breadcrumbs.admin.classStudentsList(classItem)
                : breadcrumbs.teacher.classStudentsList(classItem),
        [breadcrumbs, classItem, isAdmin],
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
                            onClick={() =>
                                router.visit(route(routeContext.showRoute, classItem.id))
                            }
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
                    variant={routeContext.role}
                    showClassColumn={false}
                    onView={
                        routeContext.studentShowRoute
                            ? (enrollment) =>
                                  router.visit(
                                      route(routeContext.studentShowRoute!, {
                                          class: classItem.id,
                                          enrollment: enrollment.id,
                                      }),
                                  )
                            : undefined
                    }
                />
            </Section>
        </AuthenticatedLayout>
    );
}
