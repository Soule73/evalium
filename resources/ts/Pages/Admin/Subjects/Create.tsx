import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Level } from '@/types';
import { breadcrumbs } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { SubjectForm } from '@/Components/features/subjects/SubjectForm';
import { route } from 'ziggy-js';

interface Props {
  levels: Level[];
}

export default function SubjectCreate({ levels }: Props) {
  const { t } = useTranslations();

  const handleCancel = () => {
    router.visit(route('admin.subjects.index'));
  };

  const translations = useMemo(() => ({
    createTitle: t('admin_pages.subjects.create_title'),
    createSubtitle: t('admin_pages.subjects.create_subtitle'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.createTitle}
      breadcrumb={breadcrumbs.admin.createSubject()}
    >
      <SubjectForm
        title={translations.createTitle}
        subtitle={translations.createSubtitle}
        levels={levels} onCancel={handleCancel} />
    </AuthenticatedLayout>
  );
}
