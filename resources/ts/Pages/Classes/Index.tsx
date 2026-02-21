import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type ClassModel, type ClassRouteContext, type Level, type PageProps } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classes: PaginationType<ClassModel>;
  levels: Level[];
  routeContext: ClassRouteContext;
}

export default function ClassIndex({ classes, levels, auth, routeContext }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const isAdmin = routeContext.role === 'admin';
  const canCreate = isAdmin && hasPermission(auth.permissions, 'create classes');

  const handleCreate = () => {
    router.visit(route('admin.classes.create'));
  };

  const translations = useMemo(
    () => ({
      title: isAdmin
        ? t('admin_pages.classes.title')
        : t('teacher_class_pages.index.title'),
      subtitle: isAdmin
        ? t('admin_pages.classes.subtitle')
        : t('teacher_class_pages.index.section_subtitle', { count: classes.total }),
      create: t('admin_pages.classes.create'),
    }),
    [t, isAdmin, classes.total],
  );

  const breadcrumb = isAdmin ? breadcrumbs.admin.classes() : breadcrumbs.teacher.classes();

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumb}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {translations.create}
            </Button>
          )
        }
      >
        <ClassList data={classes} variant={routeContext.role} levels={levels} />
      </Section>
    </AuthenticatedLayout>
  );
}
