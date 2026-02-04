import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { route } from 'ziggy-js';
import { Level } from '@/types';

export const useListLevels = () => {
  const { auth } = usePage<{ auth: { permissions: string[] } }>().props;
  const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; data: { id: number; name: string } | null }>({
    isOpen: false,
    data: null
  });

  const canCreateLevels = auth.permissions?.includes('create levels') || false;
  const canUpdateLevels = auth.permissions?.includes('update levels') || false;
  const canDeleteLevels = auth.permissions?.includes('delete levels') || false;

  const handleCreate = () => {
    router.visit(route('levels.create'));
  };

  const handleEdit = (level: Level) => {
    router.visit(route('levels.edit', level.id));
  };

  const handleToggleStatus = (level: Level) => {
    router.post(route('levels.toggle-status', level.id), {}, {
      preserveScroll: true
    });
  };

  const handleDelete = (id: number) => {
    router.delete(route('levels.destroy', id), {
      onSuccess: () => setDeleteModal({ isOpen: false, data: null })
    });
  };

  const openModal = (data: { id: number; name: string }) => {
    setDeleteModal({ isOpen: true, data });
  };

  const closeModal = () => {
    setDeleteModal({ isOpen: false, data: null });
  };

  return {
    canCreateLevels,
    canUpdateLevels,
    canDeleteLevels,
    deleteModal: {
      isOpen: deleteModal.isOpen,
      data: deleteModal.data,
      openModal,
      closeModal
    },
    handleCreate,
    handleEdit,
    handleToggleStatus,
    handleDelete
  };
};
