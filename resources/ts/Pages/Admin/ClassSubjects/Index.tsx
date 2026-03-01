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
    filterOptions: FormData;
}

export default function ClassSubjectIndex({
    classSubjects,
    filterOptions = { classes: [], subjects: [], teachers: [] },
}: Props) {
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
            <Section variant="flat" title={translations.title} subtitle={translations.subtitle}>
                <ClassSubjectList
                    data={classSubjects}
                    variant="admin"
                    classes={filterOptions.classes}
                    subjects={filterOptions.subjects}
                    teachers={filterOptions.teachers}
                    showAssessmentsColumn={false}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
