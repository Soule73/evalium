import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PageProps, ExamAssignment, Group, Level } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils';
import { route } from 'ziggy-js';
import { AlertEntry, Badge, Button, Section, StudentExamAssignmentList, TextEntry } from '@/Components';
import { trans } from '@/utils';

interface Props extends PageProps {
    group: Group & { level: Level };
    pagination: PaginationType<ExamAssignment>;
    isActiveGroup: boolean;
}

export default function Show({ group, pagination, isActiveGroup }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    };

    return (
        <AuthenticatedLayout
            title={trans('student_pages.groups_show.title', { level: group.level.name })}
            breadcrumb={breadcrumbs.studentGroupShow(group.level.name)}>
            <Section title={trans('student_pages.groups_show.group_info')}
                subtitle={
                    isActiveGroup ? (
                        <Badge label={trans('student_pages.groups_show.active_group')} type="success" />

                    ) : (
                        <Badge label={trans('student_pages.groups_show.inactive_group')} type="error" />
                    )
                }
                actions={
                    <Button
                        color="secondary"
                        variant="outline"
                        size="sm"
                        onClick={() => route('student.exams.index')}
                    >
                        {trans('student_pages.groups_show.back_to_groups')}
                    </Button>
                }
            >
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <TextEntry
                        label={trans('student_pages.groups_show.level')}
                        value={group.level.name}
                    />
                    <TextEntry
                        label={trans('student_pages.groups_show.academic_year')}
                        value={group.academic_year}
                    />
                    <TextEntry
                        label={trans('student_pages.groups_show.description')}
                        value={group.description}
                    />
                    <TextEntry
                        label={trans('student_pages.groups_show.period')}
                        value={`${formatDate(group.start_date)} - ${formatDate(group.end_date)}`}
                    />
                </div>

                {!isActiveGroup &&
                    <AlertEntry title={trans('student_pages.groups_show.not_member_title')} type="warning">
                        <p className="text-sm">
                            {trans('student_pages.groups_show.not_member_message')}
                        </p>
                    </AlertEntry>
                }
            </Section>
            <Section
                title={trans('student_pages.groups_show.exams_count', { count: pagination?.total || 0 })}
            >
                <StudentExamAssignmentList
                    data={pagination}
                    variant="full"
                    showFilters={true}
                    showSearch={true}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
