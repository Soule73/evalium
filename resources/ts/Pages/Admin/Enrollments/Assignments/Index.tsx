import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import type { Enrollment, AssessmentAssignment, PageProps, PaginationType } from '@/types';

interface ClassSubjectOption {
    id: number;
    subject_name: string;
    teacher_name: string;
}

interface Props extends PageProps {
    enrollment: Enrollment;
    assignments: PaginationType<AssessmentAssignment>;
    subjects: ClassSubjectOption[];
    filters: {
        search?: string;
        class_subject_id?: string;
        status?: string;
    };
}

export default function EnrollmentAssignmentsIndex({
    enrollment,
    assignments,
    subjects,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('admin_pages.enrollments.assignments_title'),
            subtitle: t('admin_pages.enrollments.assignments_subtitle', {
                student: enrollment.student?.name || '',
                class: enrollment.class?.name || '',
            }),
            back: t('admin_pages.enrollments.back_to_enrollment'),
        }),
        [t, enrollment.student?.name, enrollment.class?.name],
    );

    const pageBreadcrumbs = useMemo(
        () => [
            ...breadcrumbs.admin.showEnrollment(enrollment),
            { label: t('breadcrumbs.enrollment_assignments') },
        ],
        [breadcrumbs, enrollment, t],
    );

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={pageBreadcrumbs}>
            <Section
                title={translations.title}
                subtitle={translations.subtitle}
                actions={
                    <Button
                        size="sm"
                        variant="outline"
                        color="secondary"
                        onClick={() =>
                            router.visit(route('admin.enrollments.show', enrollment.id))
                        }
                    >
                        {translations.back}
                    </Button>
                }
            >
                <AssessmentList
                    data={assignments}
                    variant="class-assignment"
                    enrollment={enrollment}
                    subjects={subjects}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
