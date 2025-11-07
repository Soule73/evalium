import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PageProps, GroupWithPivot } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { BookOpenIcon, CalendarIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils';
import { route } from 'ziggy-js';
import { trans } from '@/utils';
import { Badge, DataTable, Section } from '@/Components';

interface StudentGroup extends GroupWithPivot {
    is_current: boolean;
    exams_count: number;
    completed_exams_count?: number;
}

interface Props extends PageProps {
    groups: PaginationType<StudentGroup>;
}

export default function Index({ groups }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    const dataTableConfig: DataTableConfig<StudentGroup> = {
        columns: [
            {
                key: 'name',
                label: trans('student_pages.groups_index.group'),
                render: (group) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {group.level?.name || trans('student_pages.groups_index.level_undefined')}
                        </div>
                        {group.description && (
                            <div className="text-sm text-gray-500 line-clamp-1">
                                {group.description}
                            </div>
                        )}
                    </div>
                )
            },
            {
                key: 'academic_year',
                label: trans('student_pages.groups_index.academic_year'),
                render: (group) => (
                    <span className="text-sm text-gray-900">
                        {group.academic_year || '-'}
                    </span>
                )
            },
            {
                key: 'period',
                label: trans('student_pages.groups_index.period'),
                render: (group) => (
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                        <CalendarIcon className="h-4 w-4 shrink-0 text-gray-400" />
                        <div>
                            <div>{formatDate(group.start_date)}</div>
                            <div className="text-xs text-gray-400">{trans('student_pages.groups_index.to')} {formatDate(group.end_date)}</div>
                        </div>
                    </div>
                )
            },
            {
                key: 'exams',
                label: trans('student_pages.groups_index.exams'),
                render: (group) => {
                    const examLabel = group.exams_count > 1
                        ? trans('student_pages.groups_index.exam_plural')
                        : trans('student_pages.groups_index.exam_singular');
                    const completedLabel = (group.completed_exams_count || 0) > 1
                        ? trans('student_pages.groups_index.completed_plural')
                        : trans('student_pages.groups_index.completed_singular');

                    return (
                        <div className="flex items-center gap-2 text-sm text-gray-600">
                            <BookOpenIcon className="h-4 w-4 shrink-0 text-gray-400" />
                            <div>
                                <span>{group.exams_count} {examLabel}</span>
                                {group.is_current && group.completed_exams_count !== undefined && (
                                    <div className="text-xs text-gray-500">
                                        {group.completed_exams_count} {completedLabel}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                }
            },
            {
                key: 'status',
                label: trans('student_pages.groups_index.status'),
                render: (group) => (
                    group.is_current ? (
                        <Badge label={trans('student_pages.groups_index.active')} type="success" />
                    ) : (
                        <Badge label={trans('student_pages.groups_index.inactive')} type="gray" />
                    )
                )
            },
            {
                key: 'actions',
                label: trans('student_pages.groups_index.actions'),
                render: (group) => (
                    <Link
                        href={route('student.exams.group.show', { group: group.id })}
                        className={`inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors ${group.is_current
                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                            }`}
                    >
                        {trans('student_pages.groups_index.view')}
                    </Link>
                )
            }
        ],
        emptyState: {
            title: trans('student_pages.groups_index.empty_title'),
            subtitle: trans('student_pages.groups_index.empty_subtitle'),
            icon: <UserGroupIcon className="mx-auto h-12 w-12 text-gray-400" />
        },
        searchPlaceholder: trans('student_pages.groups_index.search_placeholder'),
        perPageOptions: [10, 20, 30, 50]
    };

    return (
        <AuthenticatedLayout
            title={trans('student_pages.groups_index.title')}
            breadcrumb={breadcrumbs.studentExams()}
        >
            <Head title={trans('student_pages.groups_index.title')} />

            <div className="space-y-8">
                <Section
                    title={trans('student_pages.groups_index.groups_count', { count: groups.total })}
                    subtitle={trans('student_pages.groups_index.subtitle')}
                >
                    <DataTable
                        data={groups}
                        config={dataTableConfig}
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
