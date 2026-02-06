import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { ClassModel, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classes: PaginationType<ClassModel>;
  filters?: {
    search?: string;
    academic_year_id?: string;
    level_id?: string;
  };
}

export default function ClassIndex({ classes, auth }: Props) {
  const canCreate = hasPermission(auth.permissions, 'create classes');

  const handleCreate = () => {
    router.visit(route('admin.classes.create'));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.classes.title')}
      breadcrumb={breadcrumbs.admin.classes()}
    >
      <Section
        title={trans('admin_pages.classes.title')}
        subtitle={trans('admin_pages.classes.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.classes.create')}
            </Button>
          )
        }
      >
        <ClassList data={classes} variant="admin" />
      </Section>
    </AuthenticatedLayout>
  );
}
