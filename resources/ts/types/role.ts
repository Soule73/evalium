export interface Permission {
    id: number;
    name: string;
}

export interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

export interface GroupedPermissions {
    [category: string]: Permission[];
}

export interface RoleFormData {
    name: string;
    permissions: number[];
}
