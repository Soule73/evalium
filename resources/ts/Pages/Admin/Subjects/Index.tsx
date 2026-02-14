import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Subject, type PageProps, type Level } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section } from '@/Components';
import { SubjectList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  subjects: PaginationType<Subject>;
  levels: Level[];
}

export default function SubjectIndex({ subjects, levels, auth }: Props) {
  const canCreate = hasPermission(auth.permissions, 'create subjects');

  const handleCreate = () => {
    router.visit(route('admin.subjects.create'));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.subjects.title')}
      breadcrumb={breadcrumbs.admin.subjects()}
    >
      <Section
        title={trans('admin_pages.subjects.title')}
        subtitle={trans('admin_pages.subjects.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.subjects.create')}
            </Button>
          )
        }
      >
        <SubjectList data={subjects} variant="admin" levels={levels} />
      </Section>
    </AuthenticatedLayout>
  );
}
