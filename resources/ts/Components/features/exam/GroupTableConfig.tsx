import { Group, Exam } from '@/types';
import { UserGroupIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { DataTableConfig } from '@/types/datatable';
import { trans } from '@/utils';
import { Button } from '@/Components/ui';

interface GroupTableConfigOptions {
    exam: Exam;
    onRemove?: (group: Group) => void;
    showActions?: boolean;
    showDetailsButton?: boolean;
}

/**
 * Configuration réutilisable pour afficher la liste des groupes assignés à un examen
 */
export const getGroupTableConfig = ({
    exam,
    onRemove,
    showActions = true,
    showDetailsButton = false
}: GroupTableConfigOptions): Omit<DataTableConfig<Group>, 'data'> => {
    const columns: any[] = [
        {
            key: 'display_name',
            label: trans('components.group_table_config.group_label'),
            render: (group: Group) => (
                <div className="flex items-center space-x-3">
                    <UserGroupIcon className="h-6 w-6 text-blue-600 shrink-0" />
                    <div>
                        <div className="font-medium text-gray-900">{group.display_name}</div>
                        <div className="text-sm text-gray-500">
                            {trans('components.group_table_config.active_students_count', { count: group.active_students_count || 0 })}
                        </div>
                    </div>
                </div>
            )
        }
    ];

    if (showActions) {
        columns.push({
            key: 'actions',
            label: trans('components.group_table_config.actions_label'),
            render: (group: Group) => (
                <div className="flex space-x-2">
                    {showDetailsButton && (
                        <Button
                            type="button"
                            color="primary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(
                                route('exams.group.show', {
                                    exam: exam.id,
                                    group: group.id,
                                })
                            )}
                        >
                            {trans('components.group_table_config.view_details')}
                        </Button>
                    )}
                    <Button
                        type="button"
                        color="danger"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                            if (onRemove) {
                                onRemove(group);
                            } else {
                                // Fallback: devrait utiliser ConfirmationModal dans la page parente
                                // Cette partie ne devrait jamais être exécutée si onRemove est fourni
                                console.warn('onRemove callback non fourni pour GroupTableConfig');
                                router.delete(
                                    route('exams.groups.remove', {
                                        exam: exam.id,
                                        group: group.id,
                                    })
                                );
                            }
                        }}
                    >
                        {trans('components.group_table_config.remove')}
                    </Button>
                </div>
            )
        });
    }

    return {
        columns,
        searchPlaceholder: trans('components.group_table_config.search_placeholder'),
        emptyState: {
            title: trans('components.group_table_config.empty_title'),
            subtitle: trans('components.group_table_config.empty_subtitle'),
            icon: 'UserGroupIcon'
        },
        perPageOptions: [10, 25, 50],
    };
};
