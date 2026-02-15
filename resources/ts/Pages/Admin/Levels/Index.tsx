import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { PlusIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, ConfirmationModal, Section } from '@/Components';
import { useListLevels } from '@/hooks';
import { type Level } from '@/types';
import { LevelList } from '@/Components/shared/lists';

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
        handleCreate,
        handleEdit,
        handleToggleStatus,
        handleDelete,
    } = useListLevels();

    const translations = useMemo(() => ({
        title: t('admin_pages.levels.title'),
        subtitle: t('admin_pages.levels.subtitle'),
        create: t('admin_pages.levels.create'),
        deleteTitle: t('admin_pages.levels.delete_title'),
        delete: t('admin_pages.common.delete'),
        cancel: t('admin_pages.common.cancel'),
    }), [t]);

    const deleteMessageTranslation = useMemo(() => {
        if (!deleteModal.data) return '';
        return t('admin_pages.levels.delete_message', { name: deleteModal.data.name });
    }, [t, deleteModal.data]);

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.levels()}
        >
            <Section
                title={translations.title}
                subtitle={translations.subtitle}
                actions={
                    canCreateLevels && (
                        <Button onClick={handleCreate} size='sm'>
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
