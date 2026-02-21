import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import {
    type ClassSubject,
    type ClassModel,
    type Subject,
    type User,
    type PageProps,
} from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Section } from '@/Components';
import { ClassSubjectList } from '@/Components/shared/lists';

interface FormData {
    classes: ClassModel[];
    subjects: Subject[];
    teachers: User[];
}

interface Props extends PageProps {
    classSubjects: PaginationType<ClassSubject>;
    formData: FormData;
}

export default function ClassSubjectIndex({ classSubjects, formData }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('admin_pages.class_subjects.title'),
            subtitle: t('admin_pages.class_subjects.subtitle'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.admin.classSubjects()}
        >
            <Section title={translations.title} subtitle={translations.subtitle}>
                <ClassSubjectList
                    data={classSubjects}
                    variant="admin"
                    classes={formData.classes}
                    subjects={formData.subjects}
                    teachers={formData.teachers}
                    showAssessmentsColumn={false}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
