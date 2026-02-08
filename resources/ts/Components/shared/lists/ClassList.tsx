import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { ClassModel, Level } from '@/types';
import { Badge } from '@examena/ui';
import { trans } from '@/utils';
import type { EntityListConfig, EntityListVariant } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface ClassListProps {
  data: PaginationType<ClassModel>;
  variant?: EntityListVariant;
  levels?: Level[];
  onView?: (classItem: ClassModel) => void;
  onCreateAssessment?: (classItem: ClassModel) => void;
}

/**
 * Unified ClassList component for displaying classes across all roles
 *
 * Supports three variants:
 * - admin: Shows capacity, subject count, full CRUD actions
 * - teacher: Shows active students, assigned subjects, view/assessments actions
 * - student: Shows basic info (future implementation)
 */
export function ClassList({
  data,
  variant = 'admin',
  levels = [],
  onView,
  onCreateAssessment,
}: ClassListProps) {
  const levelFilterOptions = [
    { value: '', label: trans('admin_pages.classes.all_levels') },
    ...levels.map((level) => ({
      value: level.id,
      label: level.name,
    })),
  ];

  const config: EntityListConfig<ClassModel> = {
    entity: 'class',

    filters: [
      {
        key: 'level_id',
        labelKey: 'admin_pages.classes.level',
        type: 'select',
        options: levelFilterOptions,
        conditional: (v) => v === 'admin',
      },
    ],

    columns: [
      {
        key: 'name',
        labelKey: variant === 'admin' ? 'admin_pages.classes.name' : 'teacher_class_pages.index.name',
        render: (classItem) => {
          const levelNameDescription = `${classItem.level?.name} (${classItem.level?.description})`;
          return (<div>
            <div className="font-medium text-gray-900">
              {classItem.name}
            </div>
            <div className="text-sm text-gray-500">
              {levelNameDescription}
            </div>
          </div>)
        },
      },

      {
        key: 'students',
        labelKey: variant === 'admin' ? 'admin_pages.classes.students' : 'teacher_class_pages.index.students',
        render: (classItem, currentVariant) => {
          const activeCount = classItem.active_enrollments_count || 0;

          if (currentVariant === 'admin') {
            const maxStudents = classItem.max_students || 0;
            const percentage = maxStudents > 0 ? (activeCount / maxStudents) * 100 : 0;

            return (
              <div className="flex items-center space-x-2">
                <span className="text-sm font-medium text-gray-900">
                  {activeCount} / {maxStudents}
                </span>
                {percentage >= 90 && (
                  <Badge
                    label={trans('admin_pages.classes.full')}
                    type="warning"
                    size="sm"
                  />
                )}
              </div>
            );
          }

          return (
            <div className="flex items-center space-x-2">
              <span className="text-sm font-medium text-gray-900">{activeCount}</span>
              <span className="text-xs text-gray-500">
                {trans('teacher_class_pages.index.active_students')}
              </span>
            </div>
          );
        },
      },

      {
        key: 'subjects',
        labelKey: 'admin_pages.classes.subjects',
        render: (classItem) => (
          <div className="text-sm text-gray-600">
            {classItem.subjects_count || 0}
          </div>
        ),
        conditional: (v) => v === 'admin',
      },

      {
        key: 'my_subjects',
        labelKey: 'teacher_class_pages.index.my_subjects',
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
        conditional: (v) => v === 'teacher',
      },
    ],

    actions: [
      {
        labelKey: variant === 'admin' ? 'admin_pages.common.view' : 'teacher_class_pages.index.view',
        onClick: (item) =>
          onView?.(item) || router.visit(route(`${variant}.classes.show`, item.id)),
        color: 'secondary',
        variant: 'outline',
      },
      {
        labelKey: 'teacher_class_pages.index.assessments',
        onClick: (item) =>
          onCreateAssessment?.(item) ||
          router.visit(route('teacher.assessments.index', { class_id: item.id })),
        color: 'primary',
        variant: 'solid',
        conditional: (_item, v) => v === 'teacher',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
