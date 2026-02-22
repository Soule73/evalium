import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import type {
    ClassModel,
    Assessment,
    AssessmentAssignment,
    PageProps,
    PaginationType,
    User,
    ClassRouteContext,
} from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface SimpleFilterOption {
    id: number;
    name: string;
}

interface Props extends PageProps {
    class: ClassModel;
    assessments: PaginationType<Assessment>;
    filters: {
        search?: string;
        subject_id?: string;
        teacher_id?: string;
    };
    subjects: SimpleFilterOption[];
    teachers?: User[];
    routeContext: ClassRouteContext;
}

/**
 * Shared page displaying all assessments for a specific class.
 * Used by both admin and teacher contexts via routeContext.
 */
export default function ClassAssessments({
    class: classItem,
    assessments,
    subjects,
    teachers,
    routeContext,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const isAdmin = routeContext.role === 'admin';

    const translations = useMemo(
        () => ({
            title: t('admin_pages.classes.assessments_page_title'),
            subtitle: t('admin_pages.classes.assessments_page_subtitle'),
            back: t('commons/ui.back'),
        }),
        [t],
    );

    const pageBreadcrumbs = useMemo(
        () =>
            isAdmin
                ? breadcrumbs.admin.classAssessments(classItem)
                : breadcrumbs.teacher.classAssessments(classItem),
        [breadcrumbs, classItem, isAdmin],
    );

    const handleBack = () => {
        router.visit(route(routeContext.showRoute, classItem.id));
    };

    const handleViewAssessment = (item: Assessment | AssessmentAssignment) => {
        const assessment = item as Assessment;
        if (routeContext.assessmentShowRoute) {
            router.visit(
                route(routeContext.assessmentShowRoute, {
                    class: assessment.class_subject?.class_id ?? classItem.id,
                    assessment: assessment.id,
                }),
            );
        }
    };

    return (
        <AuthenticatedLayout
            title={`${classItem.display_name ?? classItem.name} \u2013 ${translations.title}`}
            breadcrumb={pageBreadcrumbs}
        >
            <Section
                title={`${classItem.display_name ?? classItem.name} \u2013 ${translations.title}`}
                subtitle={translations.subtitle}
                actions={
                    <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                        {translations.back}
                    </Button>
                }
            >
                <AssessmentList
                    data={assessments}
                    variant={isAdmin ? 'admin' : 'teacher'}
                    showClassColumn={false}
                    filterSubjects={subjects}
                    filterTeachers={isAdmin ? teachers : undefined}
                    onView={handleViewAssessment}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
