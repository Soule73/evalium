export interface Permission {
    id: number;
    name: string;
}

export interface Role {
    id: number;
    name: string;
    permissions?: Permission[];
    permissions_count?: number;
}

export interface GroupedPermissions {
    [category: string]: Permission[];
}
