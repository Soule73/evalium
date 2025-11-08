import { useState } from 'react';
import { router } from '@inertiajs/react';
import { GroupWithPivot, Group } from '@/types';
import { formatDate } from '@/utils';
import { UserGroupIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import { DataTableConfig } from '@/types/datatable';
import { DataTable } from '@/Components/shared';
import { Badge, Button, Select } from '@/Components/ui';
import { route } from 'ziggy-js';

interface Props {
    userId: number;
    currentGroups: GroupWithPivot[];
    availableGroups: Group[];
}

export default function StudentGroups({ userId, currentGroups, availableGroups }: Props) {
    const [isChangingGroup, setIsChangingGroup] = useState(false);
    const [selectedGroupId, setSelectedGroupId] = useState<number | null>(null);

    const handleChangeGroup = () => {
        if (!selectedGroupId) return;

        router.put(
            route('users.change-group', { user: userId }),
            { group_id: selectedGroupId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsChangingGroup(false);
                    setSelectedGroupId(null);
                },
                onError: () => {
                    setIsChangingGroup(false);
                }
            }
        );
    };

    const activeGroup = currentGroups?.find(g => g.pivot?.is_active);

    const groupsTableConfig: DataTableConfig<GroupWithPivot> = {
        columns: [
            {
                key: 'display_name',
                label: 'Groupe',
                render: (group) => (
                    <div className="flex items-center gap-3">
                        <UserGroupIcon
                            className={`h-5 w-5 ${group.pivot?.is_active ? 'text-green-600' : 'text-gray-400'
                                }`}
                        />
                        <div>
                            <p className="text-sm font-medium text-gray-900">
                                {group.display_name}
                            </p>
                            <p className="text-xs text-gray-500">
                                {group.academic_year}
                            </p>
                        </div>
                    </div>
                )
            },
            {
                key: 'level',
                label: 'Niveau',
                render: (group) => (
                    <span className="text-sm text-gray-900">
                        {group.level?.name || '-'}
                    </span>
                )
            },
            {
                key: 'enrolled_at',
                label: 'Inscrit le',
                render: (group) => (
                    <span className="text-sm text-gray-500">
                        {formatDate(group.pivot?.enrolled_at || '')}
                    </span>
                )
            },
            {
                key: 'left_at',
                label: 'Quitté le',
                render: (group) => (
                    <span className="text-sm text-gray-500">
                        {group.pivot?.left_at ? formatDate(group.pivot.left_at) : '-'}
                    </span>
                )
            },
            {
                key: 'status',
                label: 'Statut',
                render: (group) => (
                    <Badge
                        type={group.pivot?.is_active ? 'success' : 'gray'}
                        label={group.pivot?.is_active ? 'Actif' : 'Inactif'}
                    />
                )
            }
        ],
        emptyState: {
            title: 'Aucun groupe',
            subtitle: 'Cet étudiant n\'a jamais été inscrit dans un groupe.',
            icon: 'UserIcon'
        }
    };

    return (
        <div className="space-y-6">
            {activeGroup ? (
                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div className="flex items-start justify-between">
                        <div className="flex items-center gap-3">
                            <div className="shrink-0">
                                <UserGroupIcon className="h-8 w-8 text-green-600" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-green-900">
                                    {activeGroup.display_name}
                                </h3>
                                <p className="text-sm text-green-700">
                                    {activeGroup.level?.name} • {activeGroup.academic_year}
                                </p>
                                <div className="flex items-center gap-2 mt-2">
                                    <CheckCircleIcon className="h-4 w-4 text-green-600" />
                                    <span className="text-xs text-green-600 font-medium">
                                        Groupe actif
                                    </span>
                                </div>
                                <p className="text-xs text-green-600 mt-1">
                                    Inscrit le {formatDate(activeGroup.pivot?.enrolled_at || '')}
                                </p>
                            </div>
                        </div>
                        <Button
                            onClick={() => setIsChangingGroup(!isChangingGroup)}
                            size="sm"
                            variant="outline"
                            color="primary"
                        >
                            {isChangingGroup ? 'Annuler' : 'Changer de groupe'}
                        </Button>
                    </div>

                    {isChangingGroup && (
                        <div className="mt-4 pt-4 border-t border-green-200">
                            <div className="flex items-end gap-3">
                                <div className="flex-1">
                                    <Select
                                        label="Nouveau groupe"
                                        options={availableGroups
                                            .filter(g => g.id !== activeGroup.id)
                                            .map(group => ({
                                                value: group.id,
                                                label: `${group.display_name} (${group.academic_year})`
                                            }))}
                                        value={selectedGroupId ?? ''}
                                        onChange={(value) => setSelectedGroupId(Number(value))}
                                        searchable={true}
                                        placeholder="Sélectionner un groupe"
                                    />
                                </div>
                                <Button
                                    onClick={handleChangeGroup}
                                    size="sm"
                                    color="primary"
                                    disabled={!selectedGroupId}
                                >
                                    Confirmer
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            ) : (
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div className="flex items-start gap-3">
                        <div className="shrink-0">
                            <XCircleIcon className="h-8 w-8 text-yellow-600" />
                        </div>
                        <div className="flex-1">
                            <h3 className="text-lg font-semibold text-yellow-900">
                                Aucun groupe actif
                            </h3>
                            <p className="text-sm text-yellow-700 mt-1">
                                Cet étudiant n'est actuellement inscrit dans aucun groupe actif.
                            </p>
                            <div className="mt-4">
                                <Select
                                    label="Assigner à un groupe"
                                    options={availableGroups.map(group => ({
                                        value: group.id,
                                        label: `${group.display_name} (${group.academic_year})`
                                    }))}
                                    value={selectedGroupId ?? ''}
                                    onChange={(value) => setSelectedGroupId(Number(value))}
                                    searchable={true}
                                    placeholder="Sélectionner un groupe"
                                />
                                <Button
                                    onClick={handleChangeGroup}
                                    size="sm"
                                    color="primary"
                                    disabled={!selectedGroupId}
                                    className="mt-3"
                                >
                                    Assigner
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {currentGroups && currentGroups.length > 0 && (
                <div className="bg-white rounded-lg overflow-hidden">
                    <div className="p-4 border-b border-gray-200">
                        <h4 className="text-sm font-semibold text-gray-900">
                            Historique des groupes
                        </h4>
                    </div>
                    <DataTable
                        data={{
                            data: currentGroups,
                            current_page: 1,
                            per_page: currentGroups.length,
                            total: currentGroups.length,
                            last_page: 1,
                            from: 1,
                            to: currentGroups.length,
                            links: [],
                            first_page_url: '',
                            last_page_url: '',
                            next_page_url: null,
                            path: '',
                            prev_page_url: null
                        }}
                        config={groupsTableConfig}
                    />
                </div>
            )}
        </div>
    );
}
