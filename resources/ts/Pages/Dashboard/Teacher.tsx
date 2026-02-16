import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    CalendarDaysIcon,
} from '@heroicons/react/24/outline';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Section, Stat } from '@/Components';
import { AssessmentList, ClassSubjectList } from '@/Components/shared/lists';
import { type Assessment, type ClassSubject } from '@/types';
import { type PaginationType } from '@/types/datatable';

interface Stats {
    total_classes: number;
    total_subjects: number;
    total_assessments: number;
    past_assessments: number;
    upcoming_assessments: number;
}

interface Props {
    activeAssignments: PaginationType<ClassSubject>;
    pastAssessments: PaginationType<Assessment>;
    upcomingAssessments: PaginationType<Assessment>;
    stats: Stats;
}

export default function TeacherDashboard({
    stats,
    activeAssignments,
    pastAssessments,
    upcomingAssessments,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout
            title={t('dashboard.title.teacher')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            <Stat.Group columns={4} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={t('dashboard.teacher.total_classes')}
                    value={stats.total_classes}
                    icon={AcademicCapIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.total_subjects')}
                    value={stats.total_subjects}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.total_assessments')}
                    value={stats.total_assessments}
                    icon={ClipboardDocumentListIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.upcoming_assessments')}
                    value={stats.upcoming_assessments}
                    icon={CalendarDaysIcon}
                />
            </Stat.Group>

            <Section
                title={t('dashboard.teacher.active_assignments')}
                subtitle={t('dashboard.teacher.active_assignments_subtitle')}
                actions={
                    <Button
                        onClick={() => router.visit(route('teacher.classes.index'))}
                        color="secondary"
                        variant="outline"
                        size="sm"
                    >
                        {t('dashboard.teacher.view_all_classes')}
                    </Button>
                }
            >
                <ClassSubjectList
                    data={activeAssignments}
                    variant="teacher"
                    showTeacherColumn={false}
                    showAssessmentsColumn={false}
                    showPagination={false}
                />
            </Section>

            <Section
                title={t('dashboard.teacher.past_assessments')}
                subtitle={t('dashboard.teacher.past_assessments_subtitle')}
                actions={
                    <Button
                        onClick={() => router.visit(route('teacher.assessments.index'))}
                        color="secondary"
                        variant="outline"
                        size="sm"
                    >
                        {t('dashboard.teacher.view_all_assessments')}
                    </Button>
                }
            >
                <AssessmentList data={pastAssessments} variant="teacher" showPagination={false} />
            </Section>

            <Section
                title={t('dashboard.teacher.upcoming_assessments_section')}
                subtitle={t('dashboard.teacher.upcoming_assessments_subtitle')}
            >
                <AssessmentList
                    data={upcomingAssessments}
                    variant="teacher"
                    showPagination={false}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
