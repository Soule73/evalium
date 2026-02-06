import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { Subject, ClassSubject } from '@/types';
import { Badge } from '@examena/ui';
import type { EntityListConfig, EntityListVariant } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface SubjectListProps {
  data: PaginationType<Subject>;
  variant?: EntityListVariant;
  onView?: (subject: Subject) => void;
  onEdit?: (subject: Subject) => void;
  onDelete?: (subject: Subject) => void;
}

interface ClassSubjectListProps {
  data: PaginationType<ClassSubject>;
  onClassClick?: (classSubject: ClassSubject) => void;
}

/**
 * SubjectList component for displaying subjects (admin view)
 *
 * Shows subject code, name, level, and class count
 */
export function SubjectList({
  data,
  variant = 'admin',
  onView,
  onEdit,
  onDelete,
}: SubjectListProps) {
  const config: EntityListConfig<Subject> = {
    entity: 'subject',

    columns: [
      {
        key: 'code',
        labelKey: 'admin_pages.subjects.code',
        render: (subject) => (
          <div className="flex items-center space-x-2">
            <Badge label={subject.code} type="info" size="sm" />
          </div>
        ),
      },

      {
        key: 'name',
        labelKey: 'admin_pages.subjects.name',
        render: (subject) => (
          <div>
            <div className="font-medium text-gray-900">{subject.name}</div>
            {subject.description && (
              <div className="text-sm text-gray-500 truncate max-w-md">
                {subject.description}
              </div>
            )}
          </div>
        ),
      },

      {
        key: 'level',
        labelKey: 'admin_pages.subjects.level',
        render: (subject) => (
          <div className="text-sm text-gray-900">
            {subject.level?.name || '-'}
          </div>
        ),
      },

      {
        key: 'classes',
        labelKey: 'admin_pages.subjects.classes_count',
        render: (subject) => (
          <div className="text-sm text-gray-600">
            {subject.class_subjects_count || 0}
          </div>
        ),
      },
    ],

    actions: [
      {
        labelKey: 'admin_pages.common.view',
        onClick: (item) =>
          onView?.(item) || router.visit(route('admin.subjects.show', item.id)),
        color: 'secondary',
        variant: 'outline',
      },
      {
        labelKey: 'admin_pages.common.edit',
        onClick: (item) =>
          onEdit?.(item) || router.visit(route('admin.subjects.edit', item.id)),
        permission: 'update subjects',
        color: 'primary',
        variant: 'outline',
      },
      {
        labelKey: 'admin_pages.common.delete',
        onClick: (item) => onDelete?.(item),
        permission: 'delete subjects',
        color: 'danger',
        variant: 'outline',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}

/**
 * ClassSubjectList component for displaying class-subject assignments
 *
 * Used in Admin/Subjects/Show to show which classes this subject is taught in
 */
export function ClassSubjectList({ data, onClassClick }: ClassSubjectListProps) {
  const config: EntityListConfig<ClassSubject> = {
    entity: 'class_subject',

    columns: [
      {
        key: 'class',
        labelKey: 'admin_pages.subjects.class',
        render: (classSubject) => (
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
        ),
      },

      {
        key: 'teacher',
        labelKey: 'admin_pages.subjects.teacher',
        render: (classSubject) => (
          <div className="text-sm text-gray-900">
            {classSubject.teacher?.name || '-'}
          </div>
        ),
      },

      {
        key: 'coefficient',
        labelKey: 'admin_pages.subjects.coefficient',
        render: (classSubject) => (
          <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
        ),
      },
    ],

    actions: [
      {
        labelKey: 'admin_pages.common.view',
        onClick: (item) => {
          if (item.class) {
            onClassClick?.(item) || router.visit(route('admin.classes.show', item.class.id));
          }
        },
        color: 'secondary',
        variant: 'outline',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant="admin" />;
}
