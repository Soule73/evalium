import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    PlayCircleIcon,
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
    in_progress_assessments: number;
}

interface Props {
    activeAssignments: PaginationType<ClassSubject>;
    recentAssessments: PaginationType<Assessment>;
    stats: Stats;
}

export default function TeacherDashboard({ stats, activeAssignments, recentAssessments }: Props) {
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
                    title={t('dashboard.teacher.in_progress_assessments')}
                    value={stats.in_progress_assessments}
                    icon={PlayCircleIcon}
                />
            </Stat.Group>

            <Section
                title={t('dashboard.teacher.active_assignments')}
                subtitle={t('dashboard.teacher.active_assignments_subtitle')}
                actions={
                    <Button
                        onClick={() => router.visit('/teacher/class-subjects')}
                        color="secondary"
                        variant="outline"
                        size="sm"
                    >
                        {t('dashboard.teacher.view_all_assignments')}
                    </Button>
                }
            >
                <ClassSubjectList
                    data={activeAssignments}
                    variant="teacher"
                    showTeacherColumn={false}
                    showAssessmentsColumn={false}
                    showPagination={false}
                    onView={(cs) =>
                        router.visit(route('teacher.classes.show', { id: cs.class_id }))
                    }
                />
            </Section>

            <Section
                title={t('dashboard.teacher.recent_assessments')}
                subtitle={t('dashboard.teacher.recent_assessments_subtitle')}
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
                <AssessmentList
                    data={recentAssessments}
                    variant="teacher"
                    showPagination={false}
                    showClassColumn={false}
                    onView={(item) => {
                        const assessment = item as Assessment;
                        router.visit(
                            route('teacher.classes.assessments.show', {
                                class: assessment.class_subject?.class_id,
                                assessment: assessment.id,
                            }),
                        );
                    }}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
