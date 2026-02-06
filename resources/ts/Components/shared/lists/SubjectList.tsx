import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { Subject, ClassSubject } from '@/types';
import { Badge } from '@examena/ui';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface SubjectListProps {
  data: PaginationType<Subject | ClassSubject>;
  variant?: 'admin' | 'class-assignment';
  onView?: (item: Subject) => void;
  onEdit?: (item: Subject) => void;
  onDelete?: (item: Subject) => void;
  onClassClick?: (classSubject: ClassSubject) => void;
}

/**
 * Unified SubjectList component for displaying subjects and class-subject assignments
 *
 * Supports two variants:
 * - admin: Shows subjects with code, name, level, class count (Subject type)
 * - class-assignment: Shows class-subject assignments with class, teacher, coefficient (ClassSubject type)
 */
export function SubjectList({
  data,
  variant = 'admin',
  onView,
  onEdit,
  onDelete,
  onClassClick,
}: SubjectListProps) {
  type SubjectItem = Subject | ClassSubject;

  const config: EntityListConfig<SubjectItem> = {
    entity: 'subject',

    columns: [
      {
        key: 'code',
        labelKey: 'admin_pages.subjects.code',
        render: (item) => {
          const subject = item as Subject;
          return (
            <div className="flex items-center space-x-2">
              <Badge label={subject.code} type="info" size="sm" />
            </div>
          );
        },
        conditional: (v) => v === 'admin',
      },

      {
        key: 'name',
        labelKey: 'admin_pages.subjects.name',
        render: (item) => {
          const subject = item as Subject;
          return (
            <div>
              <div className="font-medium text-gray-900">{subject.name}</div>
              {subject.description && (
                <div className="text-sm text-gray-500 truncate max-w-md">
                  {subject.description}
                </div>
              )}
            </div>
          );
        },
        conditional: (v) => v === 'admin',
      },

      {
        key: 'level',
        labelKey: 'admin_pages.subjects.level',
        render: (item) => {
          const subject = item as Subject;
          return (
            <div className="text-sm text-gray-900">
              {subject.level?.name || '-'}
            </div>
          );
        },
        conditional: (v) => v === 'admin',
      },

      {
        key: 'classes',
        labelKey: 'admin_pages.subjects.classes_count',
        render: (item) => {
          const subject = item as Subject;
          return (
            <div className="text-sm text-gray-600">
              {subject.class_subjects_count || 0}
            </div>
          );
        },
        conditional: (v) => v === 'admin',
      },

      {
        key: 'class',
        labelKey: 'admin_pages.subjects.class',
        render: (item) => {
          const classSubject = item as ClassSubject;
          return (
            <div
              className="cursor-pointer hover:text-primary-600"
              onClick={() => onClassClick?.(classSubject)}
            >
              <div className="font-medium text-gray-900">
                {classSubject.class?.display_name || classSubject.class?.name}
              </div>
              <div className="text-sm text-gray-500">
                {classSubject.class?.level?.name} - {classSubject.class?.academic_year?.name}
              </div>
            </div>
          );
        },
        conditional: (v) => v === 'class-assignment',
      },

      {
        key: 'teacher',
        labelKey: 'admin_pages.subjects.teacher',
        render: (item) => {
          const classSubject = item as ClassSubject;
          return (
            <div className="text-sm text-gray-900">
              {classSubject.teacher?.name || '-'}
            </div>
          );
        },
        conditional: (v) => v === 'class-assignment',
      },

      {
        key: 'coefficient',
        labelKey: 'admin_pages.subjects.coefficient',
        render: (item) => {
          const classSubject = item as ClassSubject;
          return (
            <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
          );
        },
        conditional: (v) => v === 'class-assignment',
      },
    ],

    actions: [
      {
        labelKey: 'admin_pages.common.view',
        onClick: (item) => {
          if (variant === 'admin') {
            const subject = item as Subject;
            return onView?.(subject) || router.visit(route('admin.subjects.show', subject.id));
          } else {
            const classSubject = item as ClassSubject;
            if (classSubject.class) {
              onClassClick?.(classSubject) || router.visit(route('admin.classes.show', classSubject.class.id));
            }
          }
        },
        color: 'secondary',
        variant: 'outline',
      },
      {
        labelKey: 'admin_pages.common.edit',
        onClick: (item) => {
          const subject = item as Subject;
          return onEdit?.(subject) || router.visit(route('admin.subjects.edit', subject.id));
        },
        permission: 'update subjects',
        color: 'primary',
        variant: 'outline',
        conditional: (_item, v) => v === 'admin',
      },
      {
        labelKey: 'admin_pages.common.delete',
        onClick: (item) => {
          const subject = item as Subject;
          return onDelete?.(subject);
        },
        permission: 'delete subjects',
        color: 'danger',
        variant: 'outline',
        conditional: (_item, v) => v === 'admin',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
