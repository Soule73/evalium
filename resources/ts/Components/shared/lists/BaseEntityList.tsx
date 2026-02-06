import { usePage } from '@inertiajs/react';
import { DataTable } from '@/Components/shared';
import { DataTableConfig } from '@/types/datatable';
import { Button } from '@examena/ui';
import { trans } from '@/utils';
import type { PageProps } from '@/types';
import type { BaseEntityListProps } from './types/listConfig';

/**
 * Generic entity list component with role-based variations
 *
 * This component provides a standardized way to display paginated lists
 * with support for different variants (admin, teacher, student).
 */
export function BaseEntityList<T>({
  data,
  config,
  variant = 'admin',
  showSearch = true,
  searchPlaceholder,
  emptyMessage,
}: BaseEntityListProps<T>) {
  const { auth } = usePage<PageProps>().props;

  const hasPermission = (permission?: string): boolean => {
    if (!permission) return true;
    return auth.permissions?.includes(permission) ?? false;
  };

  const visibleColumns = config.columns.filter(
    (col) => !col.conditional || col.conditional(variant)
  );

  const visibleFilters = config.filters
    ?.filter((filter) => !filter.conditional || filter.conditional(variant))
    .filter((filter) => filter.type === 'text' || filter.type === 'select')
    .map((filter) => ({
      key: filter.key,
      label: trans(filter.labelKey),
      type: filter.type as 'text' | 'select',
      ...(filter.type === 'select' && filter.options
        ? {
          options: filter.options.map((opt) => ({
            value: String(opt.value),
            label: opt.label,
          })),
        }
        : {}),
    }));

  const dataTableConfig: DataTableConfig<T> = {
    columns: visibleColumns.map((col) => ({
      key: col.key,
      label: trans(col.labelKey),
      render: (item) => col.render(item, variant),
      sortable: col.sortable ?? true,
    })),
    filters: visibleFilters,
    searchable: showSearch,
    searchPlaceholder: searchPlaceholder ?? trans('common.search'),
    emptyState: {
      title: emptyMessage ?? trans('common.no_data'),
      subtitle: trans('common.no_results_subtitle'),
    },
    emptySearchState: {
      title: trans('common.no_search_results'),
      subtitle: trans('common.no_search_results_subtitle'),
      resetLabel: trans('common.reset_filters'),
    },
  };

  if (config.actions && config.actions.length > 0) {
    dataTableConfig.columns.push({
      key: 'actions',
      label: trans('common.actions'),
      render: (item) => {
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
                  {trans(action.labelKey)}
                </Button>
              );
            })}
          </div>
        );
      },
      sortable: false,
    });
  }

  return (
    <DataTable
      data={data as any}
      config={dataTableConfig as any}
    />
  );
}
