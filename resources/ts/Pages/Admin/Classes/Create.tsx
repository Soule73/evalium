import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { ClassForm } from '@/Components/features/classes';
import { route } from 'ziggy-js';

interface Props {
  levels: Level[];
}

export default function ClassCreate({ levels }: Props) {
  return (
    <AuthenticatedLayout
      title={trans('admin_pages.classes.create_title')}
      breadcrumb={breadcrumbs.admin.createClass()}
    >
      <ClassForm
        title={trans('admin_pages.classes.create_title')}
        subtitle={trans('admin_pages.classes.create_subtitle')}
        levels={levels}
        onCancel={() => router.visit(route('admin.classes.index'))}
      />
    </AuthenticatedLayout>
  );
}
