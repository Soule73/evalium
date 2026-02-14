import { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type ClassSubject, type ClassModel, type Subject, type User, type Semester, type PageProps } from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Section, Button } from '@/Components';
import { ClassSubjectList } from '@/Components/shared/lists';
import { CreateClassSubjectModal } from '@/Components/features';
import { PlusIcon } from '@heroicons/react/24/outline';

interface FormData {
  classes: ClassModel[];
  subjects: Subject[];
  teachers: User[];
  semesters: Semester[];
}

interface Props extends PageProps {
  classSubjects: PaginationType<ClassSubject>;
  formData: FormData;
}

export default function ClassSubjectIndex({ classSubjects, formData, auth }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const canCreate = hasPermission(auth.permissions, 'create class subjects');

  const translations = useMemo(() => ({
    title: t('admin_pages.class_subjects.title'),
    subtitle: t('admin_pages.class_subjects.subtitle'),
    createButton: t('admin_pages.class_subjects.create_button'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.title}
      breadcrumb={breadcrumbs.admin.classSubjects()}
    >
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          canCreate && (
            <Button
              size="sm"
              variant="solid"
              color="primary"
              onClick={() => setIsCreateModalOpen(true)}
            >
              <PlusIcon className="w-4 h-4 mr-1" />
              {translations.createButton}
            </Button>
          )
        }
      >
        <ClassSubjectList
          data={classSubjects}
          variant="admin"
          showAssessmentsColumn={false}
        />
      </Section>

      <CreateClassSubjectModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        formData={formData}
      />
    </AuthenticatedLayout>
  );
}
