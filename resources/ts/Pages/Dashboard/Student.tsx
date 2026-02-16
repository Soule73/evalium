import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { ChartBarIcon, CheckIcon, ClockIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { Button, Section, Stat } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { type Assessment, type AssessmentAssignment, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface DashboardStats {
    totalAssessments: number;
    completedAssessments: number;
    pendingAssessments: number;
    averageScore: number | null;
}

interface Props {
    user: User;
    stats: DashboardStats;
    assessmentAssignments: PaginationType<AssessmentAssignment & { assessment: Assessment }>;
}

export default function StudentDashboard({ user, stats, assessmentAssignments }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout
            title={t('dashboard.title.student')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            <Section
                title={t('dashboard.student.greeting', { name: user.name })}
                actions={
                    <Button
                        size="sm"
                        variant="outline"
                        className="w-max"
                        onClick={() => router.visit(route('student.assessments.index'))}
                    >
                        {t('dashboard.student.view_my_assessments')}
                    </Button>
                }
            >
                <Stat.Group columns={4} className="mb-8" data-e2e="dashboard-content">
                    <Stat.Item
                        title={t('dashboard.student.total_assessments')}
                        value={stats.totalAssessments}
                        icon={DocumentTextIcon}
                    />
                    <Stat.Item
                        title={t('dashboard.student.pending_assessments')}
                        value={stats.pendingAssessments}
                        icon={ClockIcon}
                    />
                    <Stat.Item
                        title={t('dashboard.student.completed_assessments')}
                        value={stats.completedAssessments}
                        icon={CheckIcon}
                    />
                    <Stat.Item
                        title={t('dashboard.student.average_score')}
                        value={stats.averageScore !== null ? `${stats.averageScore} / 20` : 'N/A'}
                        icon={ChartBarIcon}
                    />
                </Stat.Group>
            </Section>

            <Section
                title={t('dashboard.student.assigned_assessments')}
                actions={
                    <Button
                        size="sm"
                        variant="outline"
                        className="w-max"
                        onClick={() => router.visit(route('student.assessments.index'))}
                    >
                        {t('dashboard.student.view_all_assessments')}
                    </Button>
                }
            >
                <AssessmentList
                    data={assessmentAssignments}
                    variant="student"
                    showPagination={false}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
