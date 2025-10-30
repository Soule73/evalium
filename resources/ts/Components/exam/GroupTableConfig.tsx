import { Group, Exam } from '@/types';
import { UserGroupIcon } from '@heroicons/react/24/outline';
import { Button } from '@/Components/Button';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { DataTableConfig } from '@/types/datatable';

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
            label: 'Groupe',
            render: (group: Group) => (
                <div className="flex items-center space-x-3">
                    <UserGroupIcon className="h-6 w-6 text-blue-600 shrink-0" />
                    <div>
                        <div className="font-medium text-gray-900">{group.display_name}</div>
                        <div className="text-sm text-gray-500">
                            {group.active_students_count || 0} étudiant(s) actif(s)
                        </div>
                    </div>
                </div>
            )
        }
    ];

    if (showActions) {
        columns.push({
            key: 'actions',
            label: 'Actions',
            render: (group: Group) => (
                <div className="flex space-x-2">
                    {showDetailsButton && (
                        <Button
                            type="button"
                            color="primary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(
                                route('exams.group-details', {
                                    exam: exam.id,
                                    group: group.id,
                                })
                            )}
                        >
                            Voir détails
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
                        Retirer
                    </Button>
                </div>
            )
        });
    }

    return {
        columns,
        searchPlaceholder: 'Rechercher un groupe...',
        emptyState: {
            title: 'Aucun groupe assigné',
            subtitle: 'Cet examen n\'est pas encore assigné à des groupes',
            icon: 'UserGroupIcon'
        },
        perPageOptions: [10, 25, 50],
    };
};
