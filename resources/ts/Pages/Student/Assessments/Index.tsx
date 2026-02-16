import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type AssessmentAssignment, type PageProps } from '@/types';
import type { PaginationType } from '@/types/datatable';
import { Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface StudentAssessmentsIndexProps extends PageProps {
    assignments: PaginationType<AssessmentAssignment & { assessment: Assessment }>;
    filters: {
        status?: string;
        search?: string;
    };
}

export default function Index({ assignments }: StudentAssessmentsIndexProps) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('student_assessment_pages.index.title'),
            subtitle: t('student_assessment_pages.index.subtitle'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.student.assessments()}
        >
            <Section title={translations.title} subtitle={translations.subtitle}>
                <AssessmentList data={assignments} variant="student" />
            </Section>
        </AuthenticatedLayout>
    );
}
