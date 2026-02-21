import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type ClassModel, type Level, type PageProps } from '@/types';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';

interface Props extends PageProps {
    classes: PaginationType<ClassModel>;
    levels: Level[];
}

export default function TeacherClassIndex({ classes, levels }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('teacher_class_pages.index.title'),
            sectionTitle: t('teacher_class_pages.index.section_title'),
        }),
        [t],
    );

    const sectionSubtitleTranslation = useMemo(
        () => t('teacher_class_pages.index.section_subtitle', { count: classes.total }),
        [t, classes.total],
    );

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.teacher.classes()}>
            <Section title={translations.sectionTitle} subtitle={sectionSubtitleTranslation}>
                <ClassList data={classes} variant="teacher" levels={levels} />
            </Section>
        </AuthenticatedLayout>
    );
}
