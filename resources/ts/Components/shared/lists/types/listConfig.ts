import { type ReactNode } from 'react';
import { type PaginationType } from '@/types/datatable';

/**
 * Configuration for a column in an entity list
 */
export interface ColumnConfig<T> {
  key: string;
  labelKey: string;
  render: (item: T, variant?: EntityListVariant) => ReactNode;
  sortable?: boolean;
  conditional?: (variant: EntityListVariant) => boolean;
}

/**
 * Configuration for an action in an entity list
 */
export interface ActionConfig<T> {
  labelKey: string;
  onClick: (item: T) => void;
  permission?: string;
  color?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning';
  variant?: 'solid' | 'outline' | 'ghost';
  icon?: React.ComponentType<{ className?: string }>;
  conditional?: (item: T, variant: EntityListVariant) => boolean;
}

/**
 * Configuration for a filter in an entity list
 */
export interface FilterConfig {
  key: string;
  labelKey: string;
  type: 'select' | 'text' | 'date' | 'daterange';
  options?: Array<{ value: string | number; label: string }>;
  conditional?: (variant: EntityListVariant) => boolean;
}

/**
 * Permission configuration for entity list actions
 */
export interface PermissionConfig {
  view?: string;
  create?: string;
  update?: string;
  delete?: string;
}

/**
 * Supported variants for entity lists
 */
export type EntityListVariant = 'admin' | 'teacher' | 'student' | 'classmates' | 'class-assignment';

/**
 * Complete configuration for an entity list
 */
export interface EntityListConfig<T> {
  entity: string;
  columns: ColumnConfig<T>[];
  actions?: ActionConfig<T>[];
  filters?: FilterConfig[];
  permissions?: PermissionConfig;
}

/**
 * Props for BaseEntityList component
 */
export interface BaseEntityListProps<T> {
  data: PaginationType<T>;
  config: EntityListConfig<T>;
  variant?: EntityListVariant;
  showSearch?: boolean;
  searchPlaceholder?: string;
  emptyMessage?: string;
  showPagination?: boolean;
}
