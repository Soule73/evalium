import { useMemo } from 'react';
import { Badge, Button } from '@/Components';
import { Toggle } from '@examena/ui';
import { BaseEntityList } from './BaseEntityList';
import { EntityListConfig } from './types/listConfig';
import { Level } from '@/types';
import { PaginationType } from '@/types/datatable';
import { useTranslations } from '@/hooks';

interface LevelListProps {
  data: PaginationType<Level & { classes_count: number; active_classes_count: number }>;
  permissions?: {
    canUpdate?: boolean;
    canDelete?: boolean;
  };
  onToggleStatus?: (level: Level) => void;
  onEdit?: (level: Level) => void;
  onDelete?: (levelId: number, levelName: string) => void;
}

export function LevelList({
  data,
  permissions = {},
  onToggleStatus,
  onEdit,
  onDelete,
}: LevelListProps) {
  const { t } = useTranslations();

  type LevelWithCounts = Level & { classes_count: number; active_classes_count: number };

  const config: EntityListConfig<LevelWithCounts> = useMemo(() => ({
    entity: 'level',
    columns: [
      {
        key: 'name',
        labelKey: 'admin_pages.levels.name',
        render: (level) => (
          <div>
            <div className="text-sm font-medium text-gray-900">{level.name}</div>
            <div className="text-xs text-gray-500">
              {t('admin_pages.levels.code')}: {level.code}
            </div>
          </div>
        ),
        sortable: true,
      },
      {
        key: 'description',
        labelKey: 'admin_pages.levels.description',
        render: (level) => (
          <div className="text-sm text-gray-700 max-w-md truncate">
            {level.description || '-'}
          </div>
        ),
      },
      {
        key: 'order',
        labelKey: 'admin_pages.levels.order',
        render: (level) => <Badge label={level.order.toString()} type="gray" />,
        sortable: true,
      },
      {
        key: 'classes',
        labelKey: 'admin_pages.levels.classes_count',
        render: (level) => (
          <div className="text-sm">
            <div className="text-gray-900">{level.classes_count}</div>
            <div className="text-xs text-green-600">
              {t('admin_pages.levels.active_classes', {
                active: level.active_classes_count,
              })}
            </div>
          </div>
        ),
      },
      {
        key: 'is_active',
        labelKey: 'admin_pages.levels.status',
        render: (level) =>
          permissions.canUpdate && onToggleStatus ? (
            <Toggle
              checked={level.is_active}
              onChange={() => onToggleStatus(level)}
              size="sm"
            />
          ) : (
            <Badge
              label={
                level.is_active
                  ? t('admin_pages.common.active')
                  : t('admin_pages.common.inactive')
              }
              type={level.is_active ? 'success' : 'gray'}
            />
          ),
      },
      {
        key: 'actions',
        labelKey: 'admin_pages.common.actions',
        render: (level) =>
          permissions.canUpdate || permissions.canDelete ? (
            <div className="flex gap-2">
              {permissions.canUpdate && onEdit && (
                <Button onClick={() => onEdit(level)} size="sm" color="primary">
                  {t('admin_pages.common.edit')}
                </Button>
              )}
              {permissions.canDelete && onDelete && (
                <Button
                  onClick={() => onDelete(level.id, level.name)}
                  size="sm"
                  color="danger"
                  disabled={level.classes_count > 0}
                >
                  {t('admin_pages.common.delete')}
                </Button>
              )}
            </div>
          ) : null,
      },
    ],
  }), [permissions.canUpdate, permissions.canDelete, onToggleStatus, onEdit, onDelete, t]);

  return <BaseEntityList data={data} config={config} variant="admin" />;
}
