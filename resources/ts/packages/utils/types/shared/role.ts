export interface Permission {
    id: number;
    name: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name?: string;
    created_at?: string;
    updated_at?: string;
    permissions?: Permission[];
    permissions_count?: number;
    is_editable?: boolean;
}

export interface GroupedPermissions {
    [category: string]: Permission[];
}
