import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type PageProps, type PaginationType } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';

interface FilterOption {
    id: number;
    name: string;
}

interface Props extends PageProps {
    assessments: PaginationType<Assessment>;
    filters: {
        search?: string;
        class_id?: string;
        subject_id?: string;
        teacher_id?: string;
        type?: string;
        delivery_mode?: string;
    };
    classes: FilterOption[];
    subjects: FilterOption[];
    teachers: FilterOption[];
}

export default function AdminAssessmentsIndex({ assessments }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('admin_pages.assessments.title'),
            subtitle: t('admin_pages.assessments.subtitle'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.admin.assessments()}
        >
            <div className="space-y-6">
                <Section title={translations.title} subtitle={translations.subtitle}>
                    <AssessmentList
                        data={assessments}
                        variant="admin"
                        onView={(item) => {
                            const assessment = item as Assessment;
                            router.visit(
                                route('admin.classes.assessments.show', {
                                    class: assessment.class_subject?.class_id,
                                    assessment: assessment.id,
                                }),
                            );
                        }}
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
