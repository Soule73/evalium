import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
  type Subject,
  type ClassSubject,
  type Assessment,
  type ClassModel,
  type PageProps,
  type PaginationType,
} from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, ConfirmationModal } from '@/Components';
import { Stat } from '@/Components';
import { Badge } from '@evalium/ui';
import { SubjectList, AssessmentList } from '@/Components/shared/lists';
import { type SubjectRouteContext } from '@/types/route-context';
import { route } from 'ziggy-js';
import { AcademicCapIcon, BookOpenIcon, DocumentTextIcon } from '@heroicons/react/24/outline';

interface SubjectWithDetails extends Subject {
  classes?: ClassModel[];
  class_subjects?: ClassSubject[];
  total_assessments?: number;
}

interface Props extends PageProps {
  subject: SubjectWithDetails;
  classSubjects?: PaginationType<ClassSubject>;
  assessments?: PaginationType<Assessment>;
  filters?: Record<string, string>;
  routeContext: SubjectRouteContext;
}

export default function SubjectsShow({
  subject,
  classSubjects,
  assessments,
  filters: _filters,
  routeContext,
}: Props) {
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const isAdmin = routeContext.role === 'admin';

  const translations = useMemo(
    () => ({
      back: isAdmin ? t('commons/ui.back') : t('teacher_subject_pages.show.back'),
      edit: t('commons/ui.edit'),
      delete: t('commons/ui.delete'),
      cancel: t('commons/ui.cancel'),
      code: isAdmin
        ? t('admin_pages.subjects.code')
        : t('teacher_subject_pages.show.code'),
      level: t('admin_pages.subjects.level'),
      classesCount: isAdmin
        ? t('admin_pages.subjects.classes_count')
        : t('teacher_subject_pages.show.classes_count'),
      totalAssessments: t('teacher_subject_pages.show.total_assessments'),
      description: t('admin_pages.subjects.description'),
      classesSection: t('admin_pages.subjects.classes_section'),
      classesSectionSubtitle: t('admin_pages.subjects.classes_section_subtitle'),
      assessmentsSectionTitle: t('teacher_subject_pages.show.assessments_section_title'),
      deleteTitle: t('admin_pages.subjects.delete_title'),
    }),
    [t, isAdmin],
  );

  const assessmentsSectionSubtitle = useMemo(
    () =>
      t('teacher_subject_pages.show.assessments_section_subtitle', {
        count: assessments?.total ?? 0,
      }),
    [t, assessments?.total],
  );

  const deleteMessage = useMemo(
    () => t('admin_pages.subjects.delete_message', { name: subject.name }),
    [t, subject.name],
  );

  const handleBack = () => {
    router.visit(route(routeContext.indexRoute));
  };

  const handleEdit = () => {
    if (routeContext.editRoute) {
      router.visit(route(routeContext.editRoute, subject.id));
    }
  };

  const handleDeleteConfirm = () => {
    if (routeContext.deleteRoute) {
      router.delete(route(routeContext.deleteRoute, subject.id));
    }
  };

  const handleClassClick = (classSubject: ClassSubject) => {
    if (classSubject.class) {
      router.visit(route('admin.classes.show', classSubject.class.id));
    }
  };

  const breadcrumb = isAdmin
    ? breadcrumbs.admin.showSubject(subject)
    : breadcrumbs.teacher.showSubject(subject);

  return (
    <AuthenticatedLayout title={subject.name} breadcrumb={breadcrumb}>
      <div className="space-y-6">
        <Section
          title={subject.name}
          subtitle={isAdmin ? translations.classesSection : subject.code}
          actions={
            <div className="flex space-x-3">
              <Button
                size="sm"
                variant="outline"
                color="secondary"
                onClick={handleBack}
              >
                {translations.back}
              </Button>
              {isAdmin && routeContext.editRoute && (
                <Button
                  size="sm"
                  variant="solid"
                  color="primary"
                  onClick={handleEdit}
                >
                  {translations.edit}
                </Button>
              )}
              {isAdmin && routeContext.deleteRoute && subject.can_delete && (
                <Button
                  size="sm"
                  variant="outline"
                  color="danger"
                  onClick={() => setIsDeleteModalOpen(true)}
                >
                  {translations.delete}
                </Button>
              )}
            </div>
          }
        >
          <Stat.Group columns={3}>
            <Stat.Item
              icon={BookOpenIcon}
              title={translations.code}
              value={
                isAdmin ? (
                  <Badge label={subject.code} type="info" size="sm" />
                ) : (
                  <span className="text-sm text-gray-900">
                    {subject.code || '-'}
                  </span>
                )
              }
            />
            <Stat.Item
              icon={AcademicCapIcon}
              title={isAdmin ? translations.level : translations.classesCount}
              value={
                <span className="text-sm text-gray-900">
                  {isAdmin
                    ? subject.level?.name || '-'
                    : subject.classes?.length ?? 0}
                </span>
              }
            />
            <Stat.Item
              icon={isAdmin ? BookOpenIcon : DocumentTextIcon}
              title={isAdmin ? translations.classesCount : translations.totalAssessments}
              value={
                <span className="text-sm font-semibold text-gray-900">
                  {isAdmin
                    ? classSubjects?.total ?? 0
                    : subject.total_assessments ?? 0}
                </span>
              }
            />
          </Stat.Group>

          {isAdmin && subject.description && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <div className="text-sm font-medium text-gray-500 mb-2">
                {translations.description}
              </div>
              <div className="text-sm text-gray-900">{subject.description}</div>
            </div>
          )}
        </Section>

        {isAdmin && classSubjects && (
          <Section
            title={translations.classesSection}
            subtitle={translations.classesSectionSubtitle}
          >
            <SubjectList
              data={classSubjects}
              variant="class-assignment"
              onClassClick={handleClassClick}
            />
          </Section>
        )}

        {!isAdmin && assessments && (
          <Section
            title={translations.assessmentsSectionTitle}
            subtitle={assessmentsSectionSubtitle}
          >
            <AssessmentList data={assessments} variant="teacher" />
          </Section>
        )}
      </div>

      {isAdmin && (
        <ConfirmationModal
          isOpen={isDeleteModalOpen}
          onClose={() => setIsDeleteModalOpen(false)}
          onConfirm={handleDeleteConfirm}
          title={translations.deleteTitle}
          message={deleteMessage}
          confirmText={translations.delete}
          cancelText={translations.cancel}
          type="danger"
        />
      )}
    </AuthenticatedLayout>
  );
}
