import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Level } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { ClassForm } from '@/Components/features/classes';
import { route } from 'ziggy-js';

interface Props {
  levels: Level[];
}

export default function ClassCreate({ levels }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const translations = useMemo(() => ({
    createTitle: t('admin_pages.classes.create_title'),
    createSubtitle: t('admin_pages.classes.create_subtitle'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.createTitle}
      breadcrumb={breadcrumbs.admin.createClass()}
    >
      <ClassForm
        title={translations.createTitle}
        subtitle={translations.createSubtitle}
        levels={levels}
        onCancel={() => router.visit(route('admin.classes.index'))}
      />
    </AuthenticatedLayout>
  );
}
