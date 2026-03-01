export interface Permission {
    id: number;
    name: string;
}

export interface Role {
    id: number;
    name: string;
    permissions?: Permission[];
    permissions_count?: number;
    is_editable?: boolean;
}

export interface GroupedPermissions {
    [category: string]: Permission[];
}
