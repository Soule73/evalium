import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type ClassModel, type PageProps, type Level } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classes: PaginationType<ClassModel>;
  levels: Level[];
  filters?: {
    search?: string;
    level_id?: string;
  };
}

export default function ClassIndex({ classes, levels, auth }: Props) {
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
        <ClassList data={classes} variant="admin" levels={levels} />
      </Section>
    </AuthenticatedLayout>
  );
}
