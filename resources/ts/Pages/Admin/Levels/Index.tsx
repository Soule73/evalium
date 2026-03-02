import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@evalium/utils/types/datatable';
import { PlusIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, ConfirmationModal, Section } from '@/Components';
import { useListLevels } from '@/hooks/features/levels';
import { type Level } from '@evalium/utils/types';
import { LevelList } from '@/Components/shared/lists';
import { LevelFormModal } from '@/Components/features/levels';

interface Props {
    levels: PaginationType<Level & { classes_count: number }>;
}

export default function LevelIndex({ levels }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const {
        canCreateLevels,
        canUpdateLevels,
        canDeleteLevels,
        deleteModal,
        formModal,
        handleCreate,
        handleEdit,
        closeFormModal,
        handleToggleStatus,
        handleDelete,
    } = useListLevels();

    const translations = useMemo(
        () => ({
            title: t('admin_pages.levels.title'),
            subtitle: t('admin_pages.levels.subtitle'),
            create: t('admin_pages.levels.create'),
            deleteTitle: t('admin_pages.levels.delete_title'),
            delete: t('commons/ui.delete'),
            cancel: t('commons/ui.cancel'),
        }),
        [t],
    );

    const deleteMessageTranslation = useMemo(() => {
        if (!deleteModal.data) return '';
        return t('admin_pages.levels.delete_message', { name: deleteModal.data.name });
    }, [t, deleteModal.data]);

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.levels()}>
            <Section
                variant="flat"
                title={translations.title}
                subtitle={translations.subtitle}
                actions={
                    canCreateLevels && (
                        <Button onClick={handleCreate} size="sm">
                            <PlusIcon className="w-5 h-5 mr-2" />
                            {translations.create}
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

            <LevelFormModal
                isOpen={formModal.isOpen}
                onClose={closeFormModal}
                level={formModal.level}
            />

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={deleteModal.closeModal}
                onConfirm={() => deleteModal.data && handleDelete(deleteModal.data.id)}
                title={translations.deleteTitle}
                message={deleteMessageTranslation}
                confirmText={translations.delete}
                cancelText={translations.cancel}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
