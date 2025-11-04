import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import { route } from 'ziggy-js';
import { Group, User, PageProps } from '@/types';
import { useState } from 'react';
import { UserPlusIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { hasPermission } from '@/utils/permissions';
import { trans } from '@/utils/translations';

interface Props {
    group: Group & { active_students_count?: number };
    availableStudents: User[];
}

export default function AssignStudents({ group, availableStudents }: Props) {
    const { auth } = usePage<PageProps>().props;

    // Vérification des permissions
    const canManageGroupStudents = hasPermission(auth.permissions, 'manage group students');

    const [selectedStudents, setSelectedStudents] = useState<(number | string)[]>([]);
    const [confirmModal, setConfirmModal] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleCancel = () => {
        router.visit(route('groups.show', { group: group.id }));
    };

    const handleAssignStudents = (_ids: (number | string)[]) => {
        setConfirmModal(true);
    };

    const handleConfirmAssign = () => {
        if (selectedStudents.length === 0) return;

        setLoading(true);
        router.post(route('groups.store-students', { group: group.id }), {
            student_ids: selectedStudents
        }, {
            onSuccess: () => {
                setSelectedStudents([]);
                setConfirmModal(false);
                setLoading(false);
                router.visit(route('groups.show', { group: group.id }));
            },
            onError: () => {
                setLoading(false);
                setConfirmModal(false);
            }
        });
    };

    const handleCloseModal = () => {
        if (!loading) {
            setConfirmModal(false);
        }
    };

    const availableSlots = group.max_students - (group.active_students_count || 0);
    const maxSelectable = Math.min(availableSlots, availableStudents.length);

    // Transformer availableStudents en format PaginationType pour DataTable
    const studentsData: PaginationType<User> = {
        data: availableStudents,
        current_page: 1,
        per_page: availableStudents.length,
        total: availableStudents.length,
        last_page: 1,
        from: 1,
        to: availableStudents.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };

    const dataTableConfig: DataTableConfig<User> = {
        columns: [
            {
                key: 'name',
                label: trans('admin_pages.common.name'),
                render: (student) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{student.name}</div>
                        <div className="text-sm text-gray-500">{student.email}</div>
                    </div>
                )
            },
            {
                key: 'email',
                label: trans('admin_pages.common.email'),
                render: (student) => (
                    <span className="text-sm text-gray-600">{student.email}</span>
                )
            }
        ],
        searchPlaceholder: trans('admin_pages.groups.search_students_placeholder'),
        emptyState: {
            title: trans('admin_pages.groups.no_available_students'),
            subtitle: trans('admin_pages.groups.all_students_assigned'),
            icon: <UserPlusIcon className="w-12 h-12 mx-auto text-gray-400" />
        },
        emptySearchState: {
            title: trans('admin_pages.groups.no_student_found'),
            subtitle: trans('admin_pages.groups.no_student_match'),
            resetLabel: trans('admin_pages.groups.reset_search')
        },
        perPageOptions: [10, 25, 50, 100],
        enableSelection: canManageGroupStudents,
        maxSelectable: maxSelectable,
        selectionActions: canManageGroupStudents ? (selectedIds) => (
            <>
                <Button
                    onClick={() => handleAssignStudents(selectedIds)}
                    color="primary"
                    size="sm"
                >
                    <UserPlusIcon className="w-4 h-4 mr-2" />
                    {trans('admin_pages.groups.assign_count_button', { count: selectedIds.length })}
                </Button>
            </>
        ) : undefined,
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.groups.assign_students_page_title', { group: group.display_name })}
            breadcrumb={breadcrumbs.groupAssignStudents(group.display_name, group.id)}
        >
            <Section
                title={trans('admin_pages.groups.assign_students_page_subtitle', { group: group.display_name })}
                subtitle={trans('admin_pages.groups.available_slots_label', { available: availableSlots, max: group.max_students })}
                actions={
                    <Button
                        onClick={handleCancel}
                        color="secondary"
                        variant="outline"
                        size="sm"
                    >
                        {trans('admin_pages.users.back')}
                    </Button>
                }
            >
                {availableSlots <= 0 ? (
                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
                        <div className="text-amber-800 font-medium mb-2">
                            {trans('admin_pages.groups.group_full_title')}
                        </div>
                        <p className="text-amber-700 text-sm">
                            {trans('admin_pages.groups.group_full_description')}
                        </p>
                    </div>
                ) : (
                    <>
                        {!canManageGroupStudents ? (
                            <div className="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                                <div className="text-gray-800 font-medium mb-2">
                                    {trans('admin_pages.groups.insufficient_permission_title')}
                                </div>
                                <p className="text-gray-700 text-sm">
                                    {trans('admin_pages.groups.insufficient_permission_description')}
                                </p>
                            </div>
                        ) : (
                            <>
                                {maxSelectable < availableStudents.length && (
                                    <div className="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <p className="text-sm text-blue-800">
                                            <strong>{trans('admin_pages.groups.selection_note')}</strong> {trans('admin_pages.groups.max_selectable_note', { max: maxSelectable })}
                                        </p>
                                    </div>
                                )}

                                <DataTable
                                    data={studentsData}
                                    config={dataTableConfig}
                                    onSelectionChange={(selectedIds) => {
                                        setSelectedStudents(selectedIds);
                                    }}
                                />
                            </>
                        )}
                    </>
                )}
            </Section>

            <ConfirmationModal
                isOpen={confirmModal}
                onClose={handleCloseModal}
                onConfirm={handleConfirmAssign}
                title={trans('admin_pages.groups.assign_title')}
                message={trans('admin_pages.groups.assign_message', { count: selectedStudents.length, group: group.display_name })}
                confirmText={trans('admin_pages.groups.assign_button')}
                cancelText={trans('admin_pages.common.cancel')}
                type="info"
                icon={UserPlusIcon}
                loading={loading}
            />
        </AuthenticatedLayout>
    );
}