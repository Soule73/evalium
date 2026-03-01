export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
    permissions?: Array<{ id: number; name: string }>;
    permissions_count?: number;
    is_editable?: boolean;
}
