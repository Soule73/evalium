import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { ClassSubject } from '@/types';
import { Badge } from '@examena/ui';
import { trans } from '@/utils';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface ClassSubjectListProps {
  data: PaginationType<ClassSubject>;
  variant?: 'admin' | 'teacher';
  showClassColumn?: boolean;
  onView?: (classSubject: ClassSubject) => void;
  onReplaceTeacher?: (classSubject: ClassSubject) => void;
  onUpdateCoefficient?: (classSubject: ClassSubject) => void;
  onArchive?: (classSubject: ClassSubject) => void;
}

/**
 * Unified ClassSubjectList component for displaying class-subject assignments
 *
 * Supports variants:
 * - admin: Full management with replace teacher, update coefficient, archive actions
 * - teacher: Read-only view for teachers
 */
export function ClassSubjectList({
  data,
  variant = 'admin',
  showClassColumn = true,
  onView,
  onReplaceTeacher,
  onUpdateCoefficient,
  onArchive,
}: ClassSubjectListProps) {
  const config: EntityListConfig<ClassSubject> = {
    entity: 'class-subject',

    columns: [
      {
        key: 'class',
        labelKey: 'admin_pages.class_subjects.class',
        render: (classSubject) => {
          const levelInfo = classSubject.class?.level
            ? `${classSubject.class.level.name} (${classSubject.class.level.description})`
            : '';
          return (
            <div>
              <div className="font-medium text-gray-900">
                {classSubject.class?.name}
              </div>
              {levelInfo && (
                <div className="text-sm text-gray-500">{levelInfo}</div>
              )}
            </div>
          );
        },
        conditional: () => showClassColumn,
      },

      {
        key: 'subject',
        labelKey: 'admin_pages.class_subjects.subject',
        render: (classSubject) => (
          <div className="flex items-center space-x-2">
            <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
            <span className="text-sm text-gray-900">{classSubject.subject?.name}</span>
          </div>
        ),
      },

      {
        key: 'teacher',
        labelKey: 'admin_pages.class_subjects.teacher',
        render: (classSubject) => (
          <div>
            <div className="text-sm font-medium text-gray-900">
              {classSubject.teacher?.name || '-'}
            </div>
            {classSubject.teacher?.email && (
              <div className="text-xs text-gray-500">
                {classSubject.teacher.email}
              </div>
            )}
          </div>
        ),
      },

      {
        key: 'coefficient',
        labelKey: 'admin_pages.class_subjects.coefficient',
        render: (classSubject) => (
          <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
        ),
      },

      {
        key: 'semester',
        labelKey: 'admin_pages.class_subjects.semester',
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.semester
              ? `S${classSubject.semester.order_number}`
              : trans('admin_pages.class_subjects.all_year')}
          </div>
        ),
      },

      {
        key: 'status',
        labelKey: 'admin_pages.common.status',
        render: (classSubject) => {
          const isActive = !classSubject.valid_to;
          return (
            <Badge
              label={isActive ? trans('admin_pages.class_subjects.active') : trans('admin_pages.class_subjects.archived')}
              type={isActive ? 'success' : 'gray'}
              size="sm"
            />
          );
        },
      },

      {
        key: 'assessments',
        labelKey: 'admin_pages.classes.assessments',
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.assessments_count || 0}
          </div>
        ),
      },
    ],

    actions: [
      {
        labelKey: 'admin_pages.common.view',
        onClick: (classSubject) => {
          onView?.(classSubject) || router.visit(route('admin.class-subjects.show', classSubject.id));
        },
        color: 'secondary',
        variant: 'outline',
      },
      {
        labelKey: 'admin_pages.class_subjects.replace_teacher',
        onClick: (classSubject) => {
          onReplaceTeacher?.(classSubject) || router.visit(route('admin.class-subjects.replace-teacher', classSubject.id));
        },
        color: 'primary',
        variant: 'outline',
        permission: 'update class subjects',
        conditional: (classSubject) => !classSubject.valid_to,
      },
      {
        labelKey: 'admin_pages.class_subjects.update_coefficient',
        onClick: (classSubject) => {
          onUpdateCoefficient?.(classSubject) || router.visit(route('admin.class-subjects.edit-coefficient', classSubject.id));
        },
        color: 'warning',
        variant: 'outline',
        permission: 'update class subjects',
        conditional: (classSubject) => !classSubject.valid_to,
      },
      {
        labelKey: 'admin_pages.class_subjects.archive',
        onClick: (classSubject) => onArchive?.(classSubject),
        color: 'danger',
        variant: 'outline',
        permission: 'update class subjects',
        conditional: (classSubject, v) => v === 'admin' && !classSubject.valid_to && !!onArchive,
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
