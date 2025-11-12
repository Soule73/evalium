import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { UserGroupIcon } from '@heroicons/react/24/outline';
import { Exam, Group } from '@/types';
import { PaginationType, DataTableConfig } from '@/types/datatable';
import { trans } from '@/utils';
import { Button, DataTable, Section } from '@/Components';

interface AssignedGroupsSectionProps {
    exam: Exam;
    assignedGroups: PaginationType<Group>;
    onRemoveGroup: (group: Group) => void;
}

export function AssignedGroupsSection({ exam, assignedGroups, onRemoveGroup }: AssignedGroupsSectionProps) {
    if (!assignedGroups || assignedGroups.data.length === 0) {
        return null;
    }

    const tableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'display_name',
                label: trans('exam_pages.common.group'),
                render: (group) => (
                    <div className="flex items-center space-x-3">
                        <UserGroupIcon className="h-5 w-5 text-green-600 shrink-0" />
                        <div>
                            <div className="font-medium text-gray-900">{group.display_name}</div>
                            <div className="text-sm text-gray-500">
                                {trans('exam_pages.assign.active_students', { count: group.active_students_count || 0 })}
                            </div>
                        </div>
                    </div>
                )
            },
            {
                key: 'actions',
                label: trans('actions.actions'),
                render: (group) => (
                    <div className="flex space-x-2">
                        <Button
                            type="button"
                            color="primary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('exams.group.show', { exam: exam.id, group: group.id }))}
                        >
                            {trans('exam_pages.show.view_details')}
                        </Button>
                        <Button
                            type="button"
                            color="danger"
                            variant="outline"
                            size="sm"
                            onClick={() => onRemoveGroup(group)}
                        >
                            {trans('exam_pages.show.remove')}
                        </Button>
                    </div>
                )
            }
        ],
        searchPlaceholder: trans('exam_pages.assign.search_placeholder'),
        emptyState: {
            title: trans('exam_pages.assign.no_assigned_groups_title'),
            subtitle: trans('exam_pages.assign.no_assigned_groups_subtitle'),
            icon: 'UserGroupIcon'
        },
        perPageOptions: [10, 25, 50],
    };

    return (
        <Section
            title={trans('exam_pages.assign.assigned_groups_title')}
            subtitle={trans('exam_pages.assign.assigned_groups_subtitle', { count: assignedGroups.total })}
            collapsible
            defaultOpen={false}
        >
            <DataTable data={assignedGroups} config={tableConfig} />
        </Section>
    );
}
