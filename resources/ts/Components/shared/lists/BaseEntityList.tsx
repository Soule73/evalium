import { useMemo, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { DataTable } from '@/Components/shared';
import { type DataTableConfig } from '@/types/datatable';
import { Button } from '@examena/ui';
import { useTranslations } from '@/hooks';
import type { PageProps } from '@/types';
import type { BaseEntityListProps } from './types/listConfig';

/**
 * Generic entity list component with role-based variations
 *
 * This component provides a standardized way to display paginated lists
 * with support for different variants (admin, teacher, student).
 */
export function BaseEntityList<T extends { id?: number | string }>({
  data,
  config,
  variant = 'admin',
  showSearch = true,
  searchPlaceholder,
  emptyMessage,
  showPagination = true,
}: BaseEntityListProps<T>) {
  const { auth } = usePage<PageProps>().props;
  const { t } = useTranslations();

  const permissions = auth.permissions;

  const hasPermission = useCallback((permission?: string): boolean => {
    if (!permission) return true;
    return permissions?.includes(permission) ?? false;
  }, [permissions]);

  const visibleFilters = useMemo(() =>
    config.filters
      ?.filter((filter) => !filter.conditional || filter.conditional(variant))
      .filter((filter) => filter.type === 'text' || filter.type === 'select')
      .map((filter) => ({
        key: filter.key,
        label: t(filter.labelKey),
        type: filter.type as 'text' | 'select',
        ...(filter.type === 'select' && filter.options
          ? {
            options: filter.options.map((opt) => ({
              value: String(opt.value),
              label: opt.label,
            })),
          }
          : {}),
      })),
    [config.filters, variant, t]
  );

  const dataTableConfig: DataTableConfig<T> = useMemo(() => {
    const columns = config.columns
      .filter((col) => !col.conditional || col.conditional(variant))
      .map((col) => ({
        key: col.key,
        label: t(col.labelKey),
        render: (item: T) => col.render(item, variant),
        sortable: col.sortable ?? true,
      }));

    if (config.actions && config.actions.length > 0) {
      columns.push({
        key: 'actions',
        label: t('common.actions'),
        render: (item: T) => {
          const visibleActions = config.actions?.filter(
            (action) =>
              hasPermission(action.permission) &&
              (!action.conditional || action.conditional(item, variant))
          );

          if (!visibleActions || visibleActions.length === 0) {
            return null;
          }

          return (
            <div className="flex items-center gap-2">
              {visibleActions.map((action, idx) => {
                const Icon = action.icon;
                return (
                  <Button
                    key={idx}
                    size="sm"
                    color={action.color ?? 'primary'}
                    variant={action.variant ?? 'outline'}
                    onClick={() => action.onClick(item)}
                  >
                    {Icon && <Icon className="mr-1 h-4 w-4" />}
                    {t(action.labelKey)}
                  </Button>
                );
              })}
            </div>
          );
        },
        sortable: false,
      });
    }

    return {
      columns,
      filters: visibleFilters,
      searchable: showSearch,
      searchPlaceholder: searchPlaceholder ?? t('common.search'),
      showPagination,
      emptyState: {
        title: emptyMessage ?? t('common.no_data'),
        subtitle: t('common.no_results_subtitle'),
      },
      emptySearchState: {
        title: t('common.no_search_results'),
        subtitle: t('common.no_search_results_subtitle'),
        resetLabel: t('common.reset_filters'),
      },
    };
  }, [config.columns, config.actions, variant, visibleFilters, showSearch, searchPlaceholder, showPagination, emptyMessage, hasPermission, t]);

  return (
    <DataTable<T>
      data={data}
      config={dataTableConfig}
    />
  );
}