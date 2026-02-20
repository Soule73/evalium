import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
  type ClassModel,
  type ClassSubject,
  type PageProps,
  type PaginationType,
  type Subject,
  type User,
  type Semester,
} from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { ClassSubjectList } from '@/Components/shared/lists';
import { CreateClassSubjectModal } from '@/Components/features';
import { route } from 'ziggy-js';
import { PlusIcon } from '@heroicons/react/24/outline';

interface ClassSubjectFormData {
  classes: ClassModel[];
  subjects: Subject[];
  teachers: User[];
  semesters: Semester[];
}

interface Props extends PageProps {
  class: ClassModel;
  classSubjects: PaginationType<ClassSubject>;
  filters: {
    search?: string;
    teacher_id?: string;
    include_archived?: string;
  };
  teachers: User[];
  classSubjectFormData: ClassSubjectFormData;
}

/**
 * Admin page displaying all subject assignments for a specific class with teacher filter.
 */
export default function ClassSubjects({
  class: classItem,
  classSubjects,
  teachers,
  classSubjectFormData,
  auth,
}: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const [isAssignSubjectModalOpen, setIsAssignSubjectModalOpen] = useState(false);
  const canCreateSubject = hasPermission(auth.permissions, 'create class subjects');

  const translations = useMemo(
    () => ({
      title: t('admin_pages.classes.subjects_page_title'),
      subtitle: t('admin_pages.classes.subjects_page_subtitle'),
      back: t('admin_pages.common.back'),
      assignSubject: t('admin_pages.classes.assign_subject'),
    }),
    [t],
  );

  return (
    <AuthenticatedLayout
      title={`${classItem.name} – ${translations.title}`}
      breadcrumb={breadcrumbs.admin.classSubjectsList(classItem)}
    >
      <Section
        title={`${classItem.name} – ${translations.title}`}
        subtitle={translations.subtitle}
        actions={
          <div className="flex items-center gap-3">
            <Button
              size="sm"
              variant="outline"
              color="secondary"
              onClick={() => router.visit(route('admin.classes.show', classItem.id))}
            >
              {translations.back}
            </Button>
            {canCreateSubject && (
              <Button
                size="sm"
                variant="solid"
                color="primary"
                onClick={() => setIsAssignSubjectModalOpen(true)}
              >
                <PlusIcon className="w-4 h-4 mr-1" />
                {translations.assignSubject}
              </Button>
            )}
          </div>
        }
      >
        <ClassSubjectList
          data={classSubjects}
          variant="admin"
          showClassColumn={false}
          teachers={teachers}
          onView={(classSubject) => {
            router.visit(
              route('admin.classes.subjects.show', {
                class: classItem.id,
                class_subject: classSubject.id,
              }),
            );
          }}
        />
      </Section>

      <CreateClassSubjectModal
        isOpen={isAssignSubjectModalOpen}
        onClose={() => setIsAssignSubjectModalOpen(false)}
        formData={classSubjectFormData}
        classId={classItem.id}
        redirectTo={route('admin.classes.subjects', classItem.id)}
      />
    </AuthenticatedLayout>
  );
}
