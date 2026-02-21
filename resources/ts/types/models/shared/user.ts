import type { Role } from './role';
import type { Enrollment } from '../enrollment';
import type { ClassModel } from '../class';

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    active?: boolean;
    is_active?: boolean;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;

    roles?: Role[];
    permissions?: string[];

    previous_class?: {
        id: number;
        name: string;
        level?: { id: number; name: string } | null;
    } | null;
    current_enrollment?: Enrollment;
    enrollments?: Enrollment[];
    classes?: ClassModel[];
}
