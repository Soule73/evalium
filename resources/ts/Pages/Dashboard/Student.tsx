import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { ChartBarIcon, CheckIcon, ClockIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import {
    Badge, Button, DataTable, Section, StatCard
} from '@/Components';
import {
    AssessmentAssignment,
    User
} from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, formatDate, trans } from '@/utils';

interface Stats {
    totalAssessments: number;
    completedAssessments: number;
    pendingAssessments: number;
    inProgressAssessments: number;
    averageScore: number | null;
    completionRate: number;
}

interface Props {
    user: User;
    stats: Stats;
    assessmentAssignments: PaginationType<AssessmentAssignment>;
}

export default function StudentDashboard({ user, stats, assessmentAssignments }: Props) {
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'not_submitted':
                return <Badge label={trans('dashboard.student.status.not_submitted')} type="warning" />;
            case 'submitted':
                return <Badge label={trans('dashboard.student.status.submitted')} type="info" />;
            case 'graded':
                return <Badge label={trans('dashboard.student.status.graded')} type="success" />;
            default:
                return <Badge label={status} type="gray" />;
        }
    };

    const columns = [
        {
            key: 'title',
            label: trans('dashboard.student.table.title'),
            render: (assignment: AssessmentAssignment) => (
                <div className="font-medium text-gray-900">
                    {assignment.assessment?.title || '-'}
                </div>
            ),
        },
        {
            key: 'subject',
            label: trans('dashboard.student.table.subject'),
            render: (assignment: AssessmentAssignment) => (
                <span className="text-gray-700">
                    {assignment.assessment?.class_subject?.subject?.name || '-'}
                </span>
            ),
        },
        {
            key: 'submitted_at',
            label: trans('dashboard.student.table.submitted_at'),
            render: (assignment: AssessmentAssignment) => (
                <span className="text-gray-700">
                    {assignment.submitted_at ? formatDate(assignment.submitted_at) : '-'}
                </span>
            ),
        },
        {
            key: 'status',
            label: trans('dashboard.student.table.status'),
            render: (assignment: AssessmentAssignment) => getStatusBadge(assignment.status),
        },
        {
            key: 'actions',
            label: trans('dashboard.student.table.actions'),
            render: (assignment: AssessmentAssignment) => (
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() => router.visit(route('student.assessments.show', assignment.assessment_id))}
                >
                    {trans('dashboard.student.table.view')}
                </Button>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title={trans('dashboard.title.student')}
            breadcrumb={breadcrumbs.dashboard()}>

            <Section title={trans('dashboard.student.greeting', { name: user.name })}
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        className=' w-max'
                        onClick={() => router.visit(route('student.assessments.index'))}>
                        {trans('dashboard.student.view_my_assessments')}
                    </Button>
                }
            >
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" data-e2e='dashboard-content'>
                    <StatCard
                        title={trans('dashboard.student.total_assessments')}
                        value={`${stats.totalAssessments}`}
                        icon={DocumentTextIcon}
                        color="blue"
                    />

                    <StatCard
                        title={trans('dashboard.student.pending_assessments')}
                        value={`${stats.pendingAssessments}`}
                        icon={ClockIcon}
                        color="yellow"
                    />

                    <StatCard
                        title={trans('dashboard.student.completed_assessments')}
                        value={`${stats.completedAssessments}`}
                        icon={CheckIcon}
                        color="green"
                    />

                    <StatCard
                        title={trans('dashboard.student.average_score')}
                        value={stats.averageScore !== null ? `${stats.averageScore} / 20` : 'N/A'}
                        icon={ChartBarIcon}
                        color="red"
                    />
                </div>
            </Section>

            <Section title={trans('dashboard.student.assigned_assessments')}
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        className=' w-max'
                        onClick={() => router.visit(route('student.assessments.index'))}>
                        {trans('dashboard.student.view_all_assessments')}
                    </Button>
                }
            >
                <DataTable
                    data={assessmentAssignments}
                    config={{
                        columns,
                        emptyState: {
                            title: trans('dashboard.student.no_assessments'),
                            subtitle: trans('dashboard.student.no_assessments_subtitle'),
                        },
                    }}
                />
            </Section>
        </AuthenticatedLayout>
    );
}