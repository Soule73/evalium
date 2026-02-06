import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { SubjectForm } from '@/Components/features/subjects/SubjectForm';
import { route } from 'ziggy-js';

interface Props {
  levels: Level[];
}

export default function SubjectCreate({ levels }: Props) {
  const handleCancel = () => {
    router.visit(route('admin.subjects.index'));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.subjects.create_title')}
      breadcrumb={breadcrumbs.admin.createSubject()}
    >
      <SubjectForm
        title={trans('admin_pages.subjects.create_title')}
        subtitle={trans('admin_pages.subjects.create_subtitle')}
        levels={levels} onCancel={handleCancel} />
    </AuthenticatedLayout>
  );
}
