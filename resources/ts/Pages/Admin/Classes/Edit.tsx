import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { ClassForm } from '@/Components/features/classes';
import { route } from 'ziggy-js';

interface Props {
  class: ClassModel;
  levels: Level[];
}

export default function ClassEdit({ class: classItem, levels }: Props) {
  return (
    <AuthenticatedLayout
      title={trans('admin_pages.classes.edit_title')}
      breadcrumb={breadcrumbs.admin.editClass(classItem)}
    >
      <ClassForm
        title={trans('admin_pages.classes.edit_title')}
        subtitle={trans('admin_pages.classes.edit_subtitle')}
        classItem={classItem}
        levels={levels}
        onCancel={() => router.visit(route('admin.classes.show', classItem.id))}
      />
    </AuthenticatedLayout>
  );
}
