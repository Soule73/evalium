import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { ChartBarIcon, CheckIcon, ClockIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { Button, Section, Stat } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { Assessment, AssessmentAssignment, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';

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
    return (
        <AuthenticatedLayout
            title={trans('dashboard.title.student')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            <Section
                title={trans('dashboard.student.greeting', { name: user.name })}
                actions={
                    <Button
                        size="sm"
                        variant="outline"
                        className="w-max"
                        onClick={() => router.visit(route('student.assessments.index'))}
                    >
                        {trans('dashboard.student.view_my_assessments')}
                    </Button>
                }
            >
                <Stat.Group columns={4} className="mb-8" data-e2e="dashboard-content">
                    <Stat.Item
                        title={trans('dashboard.student.total_assessments')}
                        value={stats.totalAssessments}
                        icon={DocumentTextIcon}
                    />
                    <Stat.Item
                        title={trans('dashboard.student.pending_assessments')}
                        value={stats.pendingAssessments}
                        icon={ClockIcon}
                    />
                    <Stat.Item
                        title={trans('dashboard.student.completed_assessments')}
                        value={stats.completedAssessments}
                        icon={CheckIcon}
                    />
                    <Stat.Item
                        title={trans('dashboard.student.average_score')}
                        value={stats.averageScore !== null ? `${stats.averageScore} / 20` : 'N/A'}
                        icon={ChartBarIcon}
                    />
                </Stat.Group>
            </Section>

            <Section
                title={trans('dashboard.student.assigned_assessments')}
                actions={
                    <Button
                        size="sm"
                        variant="outline"
                        className="w-max"
                        onClick={() => router.visit(route('student.assessments.index'))}
                    >
                        {trans('dashboard.student.view_all_assessments')}
                    </Button>
                }
            >
                <AssessmentList data={assessmentAssignments} variant="student" showPagination={false} />
            </Section>
        </AuthenticatedLayout>
    );
}