import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { ClassModel, PageProps } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Badge, Button, DataTable, Section } from '@/Components';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classes: PaginationType<ClassModel>;
}

export default function TeacherClassIndex({ classes }: Props) {
  const handleView = (classItem: ClassModel) => {
    router.visit(route('teacher.classes.show', classItem.id));
  };

  const handleViewAssessments = (classItem: ClassModel) => {
    router.visit(route('teacher.assessments.index', { class_id: classItem.id }));
  };

  const dataTableConfig: DataTableConfig<ClassModel> = {
    columns: [
      {
        key: 'name',
        label: trans('teacher_pages.classes.name'),
        render: (classItem) => (
          <div>
            <div className="font-medium text-gray-900">{classItem.display_name || classItem.name}</div>
            <div className="text-sm text-gray-500">
              {classItem.level?.name} - {classItem.academic_year?.name}
            </div>
          </div>
        ),
      },
      {
        key: 'students',
        label: trans('teacher_pages.classes.students'),
        render: (classItem) => {
          const activeCount = classItem.active_enrollments_count || 0;
          return (
            <div className="flex items-center space-x-2">
              <span className="text-sm font-medium text-gray-900">{activeCount}</span>
              <span className="text-xs text-gray-500">
                {trans('teacher_pages.classes.active_students')}
              </span>
            </div>
          );
        },
      },
      {
        key: 'subjects',
        label: trans('teacher_pages.classes.my_subjects'),
        render: (classItem) => {
          const subjects = classItem.class_subjects || [];
          return (
            <div className="flex flex-wrap gap-1">
              {subjects.slice(0, 3).map((cs) => (
                <Badge
                  key={cs.id}
                  label={cs.subject?.name || '-'}
                  type="info"
                  size="sm"
                />
              ))}
              {subjects.length > 3 && (
                <Badge
                  label={`+${subjects.length - 3}`}
                  type="gray"
                  size="sm"
                />
              )}
            </div>
          );
        },
      },
      {
        key: 'actions',
        label: trans('teacher_pages.classes.actions'),
        render: (classItem) => (
          <div className="flex space-x-2">
            <Button size="sm" variant="outline" color="secondary" onClick={() => handleView(classItem)}>
              {trans('teacher_pages.classes.view')}
            </Button>
            <Button size="sm" variant="solid" color="primary" onClick={() => handleViewAssessments(classItem)}>
              {trans('teacher_pages.classes.assessments')}
            </Button>
          </div>
        ),
      },
    ],
    emptyState: {
      title: trans('teacher_pages.classes.no_classes'),
      subtitle: trans('teacher_pages.classes.no_classes_description'),
    },
  };

  return (
    <AuthenticatedLayout
      title={trans('teacher_pages.classes.title')}
      breadcrumb={breadcrumbs.teacherAssessments()}
    >
      <Section
        title={trans('teacher_pages.classes.section_title')}
        subtitle={trans('teacher_pages.classes.section_subtitle', { count: classes.total })}
      >
        <DataTable data={classes} config={dataTableConfig} />
      </Section>
    </AuthenticatedLayout>
  );
}
