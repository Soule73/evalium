import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Group, User } from '@/types';
import { UserPlusIcon } from '@heroicons/react/24/outline';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { Button, ConfirmationModal, DataTable, Section } from '@/Components';
import { useAddStudentsToGroup } from '@/hooks/features/groups';

interface Props {
    group: Group & { active_students_count?: number };
    availableStudents: PaginationType<User>;
    filters: {
        search?: string;
    };
}

export default function AddStudentsToGroup({ group, availableStudents }: Props) {

    const {
        canManageGroupStudents,
        selectedIds,
        isLoading,
        handleCancel,
        handleAssignStudents,
        handleConfirmAssign,
        confirmModal,
        maxSelectable,
        availableSlots,
        setSelectedIds

    } = useAddStudentsToGroup(group, availableStudents);


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
                                {maxSelectable < availableStudents.total && (
                                    <div className="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <p className="text-sm text-blue-800">
                                            <strong>{trans('admin_pages.groups.selection_note')}</strong> {trans('admin_pages.groups.max_selectable_note', { max: maxSelectable })}
                                        </p>
                                    </div>
                                )}

                                <DataTable
                                    data={availableStudents}
                                    config={dataTableConfig}
                                    onSelectionChange={setSelectedIds}
                                />
                            </>
                        )}
                    </>
                )}
            </Section>

            <ConfirmationModal
                isOpen={confirmModal.isOpen}
                onClose={confirmModal.closeModal}
                onConfirm={handleConfirmAssign}
                title={trans('admin_pages.groups.assign_title')}
                message={trans('admin_pages.groups.assign_message', { count: selectedIds.length, group: group.display_name })}
                confirmText={trans('admin_pages.groups.assign_button')}
                cancelText={trans('admin_pages.common.cancel')}
                type="info"
                icon={UserPlusIcon}
                loading={isLoading}
            />
        </AuthenticatedLayout>
    );
}