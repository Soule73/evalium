import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { Exam, Group, ExamAssignment } from '@/types';
import Section from '@/Components/Section';
import { DataTable } from '@/Components/DataTable';
import {
    UserGroupIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { getExamAssignmentColumns, ExamStatsCards } from '@/Components/exam';
import { trans } from '@/utils/translations';

interface Props {
    exam: Exam;
    group: Group;
    assignments: PaginationType<ExamAssignment>;
    stats: {
        total_students: number;
        completed: number;
        started: number;
        assigned: number;
        average_score: number | null;
    };
}

export default function ExamGroupDetails({ exam, group, assignments, stats }: Props) {
    const columns = getExamAssignmentColumns({
        exam,
        group,
        showActions: true
    });

    const dataTableConfig = {
        columns,
        searchPlaceholder: trans('exam_pages.group_details.search_placeholder'),
        emptyState: {
            title: trans('exam_pages.group_details.no_students_title'),
            subtitle: trans('exam_pages.group_details.no_students_subtitle'),
        },
    };

    return (
        <AuthenticatedLayout
            title={trans('exam_pages.group_details.title', { group: group.display_name, exam: exam.title })}
            breadcrumb={breadcrumbs.examGroupShow(exam.title, exam.id, group.display_name)}
        >
            <div className="space-y-6">
                <Section
                    title={
                        <div className="flex items-center space-x-3">
                            <UserGroupIcon className="h-8 w-8 text-blue-600" />
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900">{group.display_name}</h2>
                                <p className="text-sm text-gray-500">{trans('exam_pages.group_details.subtitle', { exam: exam.title })}</p>
                            </div>
                        </div>
                    }
                    actions={
                        <Button
                            onClick={() => router.visit(route('exams.assign', exam.id))}
                            color="secondary"
                            variant="outline"
                            size="sm"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            {trans('exam_pages.group_details.back')}
                        </Button>
                    }
                >
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm font-medium text-gray-700">{trans('exam_pages.group_details.level')}</p>
                                <p className="text-base text-gray-900">{group.level?.name || trans('exam_pages.group_details.not_defined')}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-700">{trans('exam_pages.group_details.active_students')}</p>
                                <p className="text-base text-gray-900">{group.active_students_count || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-700">{trans('exam_pages.group_details.exam_duration')}</p>
                                <p className="text-base text-gray-900">{exam.duration} {trans('exam_pages.group_details.minutes')}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-700">{trans('exam_pages.group_details.questions_count')}</p>
                                <p className="text-base text-gray-900">{exam.questions?.length || 0}</p>
                            </div>
                        </div>
                    </div>
                </Section>

                <Section title={trans('exam_pages.group_details.stats_title')}>
                    <ExamStatsCards stats={stats} />
                    {stats.average_score !== null && (
                        <div className="mt-4 bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-purple-900">{trans('exam_pages.group_details.average_score')}</span>
                                <span className="text-2xl font-bold text-purple-600">
                                    {Math.round(stats.average_score)}%
                                </span>
                            </div>
                        </div>
                    )}
                </Section>

                <Section
                    title={trans('exam_pages.group_details.students_title')}
                    subtitle={trans('exam_pages.group_details.students_subtitle')}
                >
                    <DataTable
                        data={assignments}
                        config={dataTableConfig}
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
