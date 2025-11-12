import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, Group } from '@/types';
import { PaginationType } from '@/types/datatable';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { Button, DataTable, ExamHeader, ExamStatsCards, getGroupTableConfig, Section } from '@/Components';

interface Props {
    exam: Exam;
    assignedGroups: PaginationType<Group>;
    stats: {
        total_assigned: number;
        completed: number;
        started: number;
        assigned: number;
        average_score: number | null;
    };
}


export default function ExamAssignments({ exam, stats, assignedGroups }: Props) {
    const groupsTableConfig = getGroupTableConfig({
        exam,
        showActions: true,
        showDetailsButton: true
    });

    return (
        <AuthenticatedLayout title={`Assignations : ${exam.title}`}
            breadcrumb={breadcrumbs.examAssignments(exam.title, exam.id)}>

            <Section
                title={trans('exam_pages.assignments.title')}
                subtitle={<ExamHeader exam={exam} showDescription={true} />}
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        onClick={() => router.visit(route('exams.assign', exam.id))}
                        color="secondary">
                        {trans('exam_pages.assignments.assign_new_groups')}
                    </Button>
                }>
                <ExamStatsCards stats={stats} className="mb-6" />
            </Section>

            {assignedGroups && assignedGroups.data.length > 0 ? (
                <Section
                    title={trans('exam_pages.assignments.groups_list_title', { count: assignedGroups.total })}
                    subtitle={trans('exam_pages.assignments.groups_list_subtitle')}
                >
                    <DataTable
                        data={assignedGroups}
                        config={groupsTableConfig}
                    />
                </Section>
            ) : (
                <Section title={trans('exam_pages.assignments.no_groups_title')}>
                    <div className="text-center py-12">
                        <svg
                            className="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <h3 className="mt-2 text-sm font-medium text-gray-900">{trans('exam_pages.assignments.no_groups_title')}</h3>
                        <p className="mt-1 text-sm text-gray-500">
                            {trans('exam_pages.assignments.no_groups_message')}
                        </p>
                        <div className="mt-6">
                            <Button
                                onClick={() => router.visit(route('exams.assign', exam.id))}
                                color="primary"
                            >
                                {trans('exam_pages.assignments.assign_groups_button')}
                            </Button>
                        </div>
                    </div>
                </Section>
            )}
        </AuthenticatedLayout>
    );
}