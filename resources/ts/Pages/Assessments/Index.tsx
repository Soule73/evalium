import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type PageProps, type PaginationType } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { type AssessmentRouteContext } from '@/types/route-context';

interface FilterOption {
  id: number;
  name: string;
}

interface Props extends PageProps {
  assessments: PaginationType<Assessment>;
  filters: Record<string, string | undefined>;
  classes?: FilterOption[];
  subjects?: FilterOption[];
  teachers?: FilterOption[];
  routeContext: AssessmentRouteContext;
}

export default function AssessmentsIndex({
  assessments,
  classes,
  teachers,
  routeContext,
}: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const isAdmin = routeContext.role === 'admin';

  const translations = useMemo(
    () => ({
      title: isAdmin
        ? t('admin_pages.assessments.title')
        : t('assessment_pages.index.title'),
      subtitle: isAdmin
        ? t('admin_pages.assessments.subtitle')
        : t('assessment_pages.index.subtitle'),
      newAssessment: t('assessment_pages.index.new_assessment'),
    }),
    [t, isAdmin],
  );

  const breadcrumb = isAdmin
    ? breadcrumbs.admin.assessments()
    : breadcrumbs.teacherAssessments();

  const handleCreate = () => {
    if (routeContext.createRoute) {
      router.visit(route(routeContext.createRoute));
    }
  };

  const adminOnView = (item: Assessment) => {
    router.visit(
      route('admin.classes.assessments.show', {
        class: item.class_subject?.class_id,
        assessment: item.id,
      }),
    );
  };

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumb}>
      <div className="space-y-6">
        <Section
          title={translations.title}
          subtitle={translations.subtitle}
          actions={
            !isAdmin && routeContext.createRoute ? (
              <Button
                size="sm"
                variant="outline"
                color="secondary"
                onClick={handleCreate}
              >
                {translations.newAssessment}
              </Button>
            ) : undefined
          }
        >
          {isAdmin ? (
            <AssessmentList
              data={assessments}
              variant="admin"
              filterTeachers={teachers}
              onView={(item) => adminOnView(item as Assessment)}
            />
          ) : (
            <AssessmentList
              data={assessments}
              variant="teacher"
              classes={classes}
            />
          )}
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
