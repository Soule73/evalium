import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassModel, type Assessment, type PageProps, type PaginationType } from '@/types';
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
    teachers: SimpleFilterOption[];
}

/**
 * Admin page displaying all assessments for a specific class with subject and teacher filters.
 */
export default function ClassAssessments({
    class: classItem,
    assessments,
    subjects,
    teachers,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('admin_pages.classes.assessments_page_title'),
            subtitle: t('admin_pages.classes.assessments_page_subtitle'),
            back: t('commons/ui.back'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={`${classItem.display_name ?? classItem.name} – ${translations.title}`}
            breadcrumb={breadcrumbs.admin.classAssessments(classItem)}
        >
            <Section
                title={`${classItem.display_name ?? classItem.name} – ${translations.title}`}
                subtitle={translations.subtitle}
                actions={
                    <Button
                        size="sm"
                        variant="outline"
                        color="secondary"
                        onClick={() => router.visit(route('admin.classes.show', classItem.id))}
                    >
                        {translations.back}
                    </Button>
                }
            >
                <AssessmentList
                    data={assessments}
                    variant="admin"
                    showClassColumn={false}
                    filterSubjects={subjects}
                    filterTeachers={teachers}
                    onView={(item) => {
                        const assessment = item as Assessment;
                        router.visit(
                            route('admin.classes.assessments.show', {
                                class: assessment.class_subject?.class_id ?? classItem.id,
                                assessment: assessment.id,
                            }),
                        );
                    }}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
