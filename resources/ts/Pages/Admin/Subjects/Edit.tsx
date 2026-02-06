import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Subject, Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { SubjectForm } from '@/Components/features/subjects/SubjectForm';
import { route } from 'ziggy-js';

interface Props {
  subject: Subject;
  levels: Level[];
}

export default function SubjectEdit({ subject, levels }: Props) {
  const handleCancel = () => {
    router.visit(route('admin.subjects.show', subject.id));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.subjects.edit_title')}
      breadcrumb={breadcrumbs.admin.editSubject(subject)}
    >
      <SubjectForm
        title={trans('admin_pages.subjects.edit_title')}
        subtitle={trans('admin_pages.subjects.edit_subtitle')}
        subject={subject} levels={levels} onCancel={handleCancel} />
    </AuthenticatedLayout>
  );
}
