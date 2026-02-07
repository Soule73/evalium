import { useState } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { ClassSubject, ClassModel, Subject, User, Semester, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
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
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const canCreate = hasPermission(auth.permissions, 'create class subjects');

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.title')}
      breadcrumb={breadcrumbs.admin.classSubjects()}
    >
      <Section
        title={trans('admin_pages.class_subjects.title')}
        subtitle={trans('admin_pages.class_subjects.subtitle')}
        actions={
          canCreate && (
            <Button
              size="sm"
              variant="solid"
              color="primary"
              onClick={() => setIsCreateModalOpen(true)}
            >
              <PlusIcon className="w-4 h-4 mr-1" />
              {trans('admin_pages.class_subjects.create_button')}
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
