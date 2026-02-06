import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { AcademicYear, Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { ClassForm } from '@/Components/features/classes';
import { route } from 'ziggy-js';

interface Props {
  academicYears: AcademicYear[];
  levels: Level[];
}

export default function ClassCreate({ academicYears, levels }: Props) {
  return (
    <AuthenticatedLayout
      title={trans('admin_pages.classes.create_title')}
      breadcrumb={breadcrumbs.admin.createClass()}
    >
      <ClassForm
        title={trans('admin_pages.classes.create_title')}
        subtitle={trans('admin_pages.classes.create_subtitle')}
        academicYears={academicYears}
        levels={levels}
        onCancel={() => router.visit(route('admin.classes.index'))}
      />
    </AuthenticatedLayout>
  );
}
