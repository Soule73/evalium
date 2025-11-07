import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { ChartBarIcon, CheckIcon, ClockIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { Button, Section, StatCard, StudentExamAssignmentList } from '@/Components';
import { ExamAssignment, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';

interface Stats {
    totalExams: number;
    completedExams: number;
    pendingExams: number;
    averageScore: number;
}

interface Props {
    user: User;
    stats: Stats;
    examAssignments: PaginationType<ExamAssignment>;
}

export default function StudentDashboard({ user, stats, examAssignments }: Props) {
    return (
        <AuthenticatedLayout title={trans('dashboard.title.student')}
            breadcrumb={breadcrumbs.dashboard()}
        >

            <Section title={trans('dashboard.student.greeting', { name: user.name })}
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        className=' w-max'
                        onClick={() => router.visit(route('student.exams.index'))}>
                        {trans('dashboard.student.view_my_exams')}
                    </Button>
                }
            >
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <StatCard
                        title={trans('dashboard.student.total_exams')}
                        value={`${stats.totalExams}`}
                        icon={DocumentTextIcon}
                        color="blue"
                    />

                    <StatCard
                        title={trans('dashboard.student.pending_exams')}
                        value={`${stats.pendingExams}`}
                        icon={ClockIcon}
                        color="yellow"
                    />

                    <StatCard
                        title={trans('dashboard.student.completed_exams')}
                        value={`${stats.completedExams}`}
                        icon={CheckIcon}
                        color="green"
                    />

                    <StatCard
                        title={trans('dashboard.student.average_score')}
                        value={`${stats.averageScore} / 20`}
                        icon={ChartBarIcon}
                        color="red"
                    />
                </div>

            </Section>

            <Section title={trans('dashboard.student.assigned_exams')}
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        className=' w-max'
                        onClick={() => router.visit(route('student.exams.index'))}>
                        {trans('dashboard.student.view_all_exams')}
                    </Button>
                }
            >
                <StudentExamAssignmentList
                    data={examAssignments}
                    variant="dashboard"
                    showFilters={false}
                    showSearch={true}
                />
            </Section>
        </AuthenticatedLayout>
    );
}