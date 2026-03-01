import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type AssessmentAssignment, type PageProps } from '@/types';
import type { PaginationType } from '@/types/datatable';
import { Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface ClassSubjectOption {
    id: number;
    subject_name: string;
    teacher_name: string;
}

interface StudentAssessmentsIndexProps extends PageProps {
    assignments: PaginationType<AssessmentAssignment & { assessment: Assessment }>;
    subjects: ClassSubjectOption[];
    filters: {
        status?: string;
        search?: string;
        class_subject_id?: string;
    };
}

export default function Index({ assignments, subjects }: StudentAssessmentsIndexProps) {
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
                <AssessmentList data={assignments} variant="student" subjects={subjects} />
            </Section>
        </AuthenticatedLayout>
    );
}
