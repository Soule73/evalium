import { ReactNode } from 'react';

export interface PaginationType<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface FilterConfig {
    key: string;
    label: string;
    type: 'text' | 'select';
    placeholder?: string;
    options?: { value: string; label: string }[];
    defaultValue?: string;
}

export interface ColumnConfig<T> {
    key: string;
    label: string;
    className?: string;
    render?: (item: T, index: number) => ReactNode;
    sortable?: boolean;
}

export interface DataTableConfig<T> {
    columns: ColumnConfig<T>[];
    filters?: FilterConfig[];
    emptyState?: {
        title: string;
        subtitle: string;
        icon?: ReactNode;
        actions?: ReactNode;
    };
    emptySearchState?: {
        title: string;
        subtitle: string;
        icon?: ReactNode;
        resetLabel?: string;
    };
    searchPlaceholder?: string;
    perPageOptions?: number[];
    enableSelection?: boolean;
    maxSelectable?: number;
    isSelectable?: (item: T) => boolean;
    selectionActions?: (selectedIds: (number | string)[]) => ReactNode;
    searchable?: boolean;
    onSearch?: (search: string) => void;
    showPagination?: boolean;
}

export interface DataTableState {
    search: string;
    filters: Record<string, string>;
    page: number;
    perPage: number;
}

export interface DataTableProps<T> {
    data: PaginationType<T>;
    config: DataTableConfig<T>;
    onStateChange?: (state: DataTableState) => void;
    onSelectionChange?: (selectedIds: (number | string)[]) => void;
    isLoading?: boolean;
    className?: string;
    testIdSelectAllCheckbox?: string;
    testIdTableBody?: string;
    testIdEmptyState?: string;
    testIdResetFiltersButton?: string;
    testIdEmptyResetFiltersButton?: string;
    dataTableSearchInputId?: string;
}