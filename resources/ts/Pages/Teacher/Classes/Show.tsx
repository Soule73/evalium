import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Enrollment, ClassSubject, Assessment, AssessmentAssignment, PageProps, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section, Stat } from '@/Components';
import { ClassSubjectList, AssessmentList, EnrollmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon, BookOpenIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface EnrollmentWithStudent extends Enrollment {
  student?: User;
}

interface Props extends PageProps {
  class: ClassModel;
  subjects: PaginationType<ClassSubject>;
  assessments: PaginationType<Assessment>;
  students: PaginationType<EnrollmentWithStudent>;
}

export default function TeacherClassShow({ class: classItem, subjects, assessments, students }: Props) {
  const handleBack = () => {
    router.visit(route('teacher.classes.index'));
  };

  const handleViewAssessments = () => {
    router.visit(route('teacher.assessments.index', { class_id: classItem.id }));
  };

  const handleCreateAssessment = (classSubject: ClassSubject) => {
    router.visit(route('teacher.assessments.create', { class_subject_id: classSubject.id }));
  };

  const handleViewAssessment = (item: Assessment | AssessmentAssignment) => {
    const assessment = item as Assessment;
    router.visit(route('teacher.assessments.show', assessment.id));
  };

  return (
    <AuthenticatedLayout
      title={classItem.name}
      breadcrumb={breadcrumbs.teacher.showClass(classItem)}
    >
      <div className="space-y-6">
        <Section
          title={classItem.name}
          subtitle={trans('teacher_class_pages.show.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('teacher_class_pages.show.back')}
              </Button>
              <Button size="sm" variant="solid" color="primary" onClick={handleViewAssessments}>
                {trans('teacher_class_pages.show.all_assessments')}
              </Button>
            </div>
          }
        >
          <Stat.Group columns={4}>
            <Stat.Item
              icon={AcademicCapIcon}
              title={trans('teacher_class_pages.show.level')}
              value={<span className="text-sm text-gray-900">{classItem.level?.name || '-'}</span>}
            />
            <Stat.Item
              icon={CalendarIcon}
              title={trans('teacher_class_pages.show.academic_year')}
              value={<span className="text-sm text-gray-900">{classItem.academic_year?.name || '-'}</span>}
            />
            <Stat.Item
              icon={UserGroupIcon}
              title={trans('teacher_class_pages.show.students')}
              value={<span className="text-sm font-semibold text-gray-900">{students.total}</span>}
            />
            <Stat.Item
              icon={BookOpenIcon}
              title={trans('teacher_class_pages.show.my_subjects')}
              value={<span className="text-sm font-semibold text-gray-900">{subjects.total}</span>}
            />
          </Stat.Group>
        </Section>

        <Section
          title={trans('teacher_class_pages.show.subjects_section_title')}
          subtitle={trans('teacher_class_pages.show.subjects_section_subtitle')}
        >
          <ClassSubjectList
            data={subjects}
            variant="teacher"
            showClassColumn={false}
            showTeacherColumn={false}
            onCreateAssessment={handleCreateAssessment}
          />
        </Section>

        <Section
          title={trans('teacher_class_pages.show.recent_assessments_title')}
          subtitle={trans('teacher_class_pages.show.recent_assessments_subtitle')}
          actions={
            assessments.total > 0 && (
              <Button size="sm" variant="outline" color="secondary" onClick={handleViewAssessments}>
                {trans('teacher_class_pages.show.view_all')}
              </Button>
            )
          }
        >
          <AssessmentList
            data={assessments}
            variant="teacher"
            showClassColumn={false}
            onView={handleViewAssessment}
          />
        </Section>

        <Section
          title={trans('teacher_class_pages.show.students_section_title')}
          subtitle={trans('teacher_class_pages.show.students_section_subtitle', { count: students.total })}
        >
          <EnrollmentList
            data={students as PaginationType<Enrollment>}
            variant="teacher"
            showClassColumn={false}
          />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
