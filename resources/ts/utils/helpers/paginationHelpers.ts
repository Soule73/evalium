import { Group } from '@/types';
import { PaginationType } from '@/types/datatable';

/**
 * Transformer un tableau de groupes en format PaginationType pour DataTable
 */
export const groupsToPaginationType = (groups: Group[] = []): PaginationType<Group> => {
    return {
        data: groups,
        current_page: 1,
        per_page: groups.length || 0,
        total: groups.length || 0,
        last_page: 1,
        from: 1,
        to: groups.length || 0,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };
};
