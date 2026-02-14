import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Enrollment, type ClassModel, type PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section } from '@/Components';
import { EnrollmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  enrollments: PaginationType<Enrollment>;
  classes: ClassModel[];
  filters?: {
    search?: string;
    class_id?: string;
    status?: string;
  };
}

export default function EnrollmentIndex({ enrollments, classes, auth }: Props) {
  const canCreate = hasPermission(auth.permissions, 'create enrollments');

  const handleCreate = () => {
    router.visit(route('admin.enrollments.create'));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.enrollments.title')}
      breadcrumb={breadcrumbs.admin.enrollments()}
    >
      <Section
        title={trans('admin_pages.enrollments.title')}
        subtitle={trans('admin_pages.enrollments.subtitle')}
        actions={
          canCreate && (
            <Button
              size="sm"
              variant="solid"
              color="primary"
              onClick={handleCreate}
            >
              {trans('admin_pages.enrollments.create')}
            </Button>
          )
        }
      >
        <EnrollmentList data={enrollments} classes={classes} variant="admin" />
      </Section>
    </AuthenticatedLayout>
  );
}
