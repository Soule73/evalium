import { UserGroupIcon, UserPlusIcon } from '@heroicons/react/24/outline';
import { Group } from '@/types';
import { PaginationType, DataTableConfig } from '@/types/datatable';
import { trans } from '@/utils';
import { Button, DataTable, Section } from '@/Components';

interface AvailableGroupsSectionProps {
    availableGroups: PaginationType<Group>;
    isProcessing: boolean;
    onAssignGroups: (selectedIds: (string | number)[]) => void;
}

export function AvailableGroupsSection({
    availableGroups,
    isProcessing,
    onAssignGroups
}: AvailableGroupsSectionProps) {
    const tableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'display_name',
                label: trans('exam_pages.common.group'),
                render: (group) => (
                    <div className="flex items-center space-x-3">
                        <UserGroupIcon className="h-5 w-5 text-gray-400 shrink-0" />
                        <div>
                            <div className="font-medium text-gray-900">{group.display_name}</div>
                            <div className="text-sm text-gray-500">
                                {trans('exam_pages.assign.active_students', { count: group.active_students_count || 0 })}
                            </div>
                        </div>
                    </div>
                )
            }
        ],
        searchPlaceholder: trans('exam_pages.assign.search_placeholder'),
        emptyState: {
            title: trans('exam_pages.assign.all_assigned'),
            subtitle: trans('exam_pages.assign.all_assigned_subtitle'),
            icon: 'UserGroupIcon'
        },
        emptySearchState: {
            title: trans('exam_pages.assign.no_groups_found'),
            subtitle: trans('exam_pages.assign.no_groups_found_subtitle'),
            resetLabel: trans('exam_pages.assign.reset_search')
        },
        perPageOptions: [10, 25, 50],
        enableSelection: true,
        selectionActions: (selectedIds) => {
            const plural = selectedIds.length > 1 ? trans('exam_pages.common.s') : '';
            return (
                <Button
                    onClick={() => onAssignGroups(selectedIds)}
                    color="primary"
                    size="sm"
                    loading={isProcessing}
                    disabled={isProcessing || selectedIds.length === 0}
                >
                    <UserPlusIcon className="w-4 h-4 mr-2" />
                    {trans('exam_pages.assign.assign_to_groups', { count: selectedIds.length, plural })}
                </Button>
            );
        },
    };

    return (
        <Section
            title={trans('exam_pages.assign.select_groups_title')}
            subtitle={trans('exam_pages.assign.select_groups_subtitle')}
        >
            <DataTable data={availableGroups} config={tableConfig} />
        </Section>
    );
}
