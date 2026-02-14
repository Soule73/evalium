import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Subject, type Level } from '@/types';
import { breadcrumbs } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { SubjectForm } from '@/Components/features/subjects/SubjectForm';
import { route } from 'ziggy-js';

interface Props {
  subject: Subject;
  levels: Level[];
}

export default function SubjectEdit({ subject, levels }: Props) {
  const { t } = useTranslations();

  const handleCancel = () => {
    router.visit(route('admin.subjects.show', subject.id));
  };

  const translations = useMemo(() => ({
    editTitle: t('admin_pages.subjects.edit_title'),
    editSubtitle: t('admin_pages.subjects.edit_subtitle'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.editTitle}
      breadcrumb={breadcrumbs.admin.editSubject(subject)}
    >
      <SubjectForm
        title={translations.editTitle}
        subtitle={translations.editSubtitle}
        subject={subject} levels={levels} onCancel={handleCancel} />
    </AuthenticatedLayout>
  );
}
