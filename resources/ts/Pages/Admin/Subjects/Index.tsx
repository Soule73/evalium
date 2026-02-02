import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { Subject, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section } from '@/Components';
import { route } from 'ziggy-js';

interface Props extends PageProps {
    subjects: PaginationType<Subject>;
}

export default function SubjectIndex({ subjects, auth }: Props) {
    const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; subject: Subject | null }>({
        isOpen: false,
        subject: null,
    });

    const canCreate = hasPermission(auth.permissions, 'create subjects');
    const canUpdate = hasPermission(auth.permissions, 'update subjects');
    const canDelete = hasPermission(auth.permissions, 'delete subjects');

    const handleCreate = () => {
        router.visit(route('admin.subjects.create'));
    };

    const handleView = (subject: Subject) => {
        router.visit(route('admin.subjects.show', subject.id));
    };

    const handleEdit = (subject: Subject) => {
        router.visit(route('admin.subjects.edit', subject.id));
    };

    const handleDeleteClick = (subject: Subject) => {
        setDeleteModal({ isOpen: true, subject });
    };

    const handleDeleteConfirm = () => {
        if (deleteModal.subject) {
            router.delete(route('admin.subjects.destroy', deleteModal.subject.id), {
                onSuccess: () => {
                    setDeleteModal({ isOpen: false, subject: null });
                },
            });
        }
    };

    const dataTableConfig: DataTableConfig<Subject> = {
        columns: [
            {
                key: 'code',
                label: trans('admin_pages.subjects.code'),
                render: (subject) => (
                    <div className="flex items-center space-x-2">
                        <Badge label={subject.code} type="info" size="sm" />
                    </div>
                ),
            },
            {
                key: 'name',
                label: trans('admin_pages.subjects.name'),
                render: (subject) => (
                    <div>
                        <div className="font-medium text-gray-900">{subject.name}</div>
                        {subject.description && (
                            <div className="text-sm text-gray-500 truncate max-w-md">{subject.description}</div>
                        )}
                    </div>
                ),
            },
            {
                key: 'level',
                label: trans('admin_pages.subjects.level'),
                render: (subject) => (
                    <div className="text-sm text-gray-900">
                        {subject.level?.name || '-'}
                    </div>
                ),
            },
            {
                key: 'classes',
                label: trans('admin_pages.subjects.classes_count'),
                render: (subject) => (
                    <div className="text-sm text-gray-600">
                        {subject.class_subjects_count || 0}
                    </div>
                ),
            },
            {
                key: 'actions',
                label: trans('admin_pages.common.actions'),
                render: (subject) => (
                    <div className="flex space-x-2">
                        <Button size="sm" variant="outline" color="secondary" onClick={() => handleView(subject)}>
                            {trans('admin_pages.common.view')}
                        </Button>
                        {canUpdate && (
                            <Button size="sm" variant="outline" color="primary" onClick={() => handleEdit(subject)}>
                                {trans('admin_pages.common.edit')}
                            </Button>
                        )}
                        {canDelete && (
                            <Button size="sm" variant="outline" color="danger" onClick={() => handleDeleteClick(subject)}>
                                {trans('admin_pages.common.delete')}
                            </Button>
                        )}
                    </div>
                ),
            },
        ],
        filters: [],
        emptyState: {
            title: trans('admin_pages.subjects.empty_title'),
            subtitle: trans('admin_pages.subjects.empty_subtitle'),
        },
    };

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.subjects.title')}
            breadcrumb={breadcrumbs.admin.subjects()}
        >
            <Section
                title={trans('admin_pages.subjects.title')}
                subtitle={trans('admin_pages.subjects.subtitle')}
                actions={
                    canCreate && (
                        <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
                            {trans('admin_pages.subjects.create')}
                        </Button>
                    )
                }
            >
                <DataTable data={subjects} config={dataTableConfig} />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, subject: null })}
                onConfirm={handleDeleteConfirm}
                title={trans('admin_pages.subjects.delete_title')}
                message={trans('admin_pages.subjects.delete_message', { name: deleteModal.subject?.name || '' })}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
