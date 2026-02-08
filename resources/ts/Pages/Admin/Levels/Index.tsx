import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { PlusIcon } from '@heroicons/react/24/outline';
import { breadcrumbs, trans } from '@/utils';
import { Button, ConfirmationModal, Section } from '@/Components';
import { useListLevels } from '@/hooks';
import { Level } from '@/types';
import { LevelList } from '@/Components/shared/lists';

interface Props {
    levels: PaginationType<Level & { classes_count: number; active_classes_count: number }>;
}

export default function LevelIndex({ levels }: Props) {
    const {
        canCreateLevels,
        canUpdateLevels,
        canDeleteLevels,
        deleteModal,
        handleCreate,
        handleEdit,
        handleToggleStatus,
        handleDelete,
    } = useListLevels();

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.levels.title')}
            breadcrumb={breadcrumbs.levels()}
        >
            <Section
                title={trans('admin_pages.levels.title')}
                subtitle={trans('admin_pages.levels.subtitle')}
                actions={
                    canCreateLevels && (
                        <Button onClick={handleCreate} size='sm'>
                            <PlusIcon className="w-5 h-5 mr-2" />
                            {trans('admin_pages.levels.create')}
                        </Button>
                    )
                }
            >
                <LevelList
                    data={levels}
                    permissions={{
                        canUpdate: canUpdateLevels,
                        canDelete: canDeleteLevels,
                    }}
                    onToggleStatus={handleToggleStatus}
                    onEdit={handleEdit}
                    onDelete={(levelId, levelName) =>
                        deleteModal.openModal({ id: levelId, name: levelName })
                    }
                />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={deleteModal.closeModal}
                onConfirm={() => deleteModal.data && handleDelete(deleteModal.data.id)}
                title={trans('admin_pages.levels.delete_title')}
                message={trans(
                    'admin_pages.levels.delete_message',
                    { name: deleteModal.data?.name || '' }
                )}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
