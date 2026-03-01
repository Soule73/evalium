import type { ClassModel } from './class';
import type { User } from './shared';

export type EnrollmentStatus = 'active' | 'withdrawn' | 'completed';

export interface Enrollment {
    id: number;
    class_id: number;
    student_id: number;
    status: EnrollmentStatus;
    enrolled_at: string;
    withdrawn_at?: string;
    created_at: string;
    updated_at: string;

    class?: ClassModel;
    student?: User;

    is_active?: boolean;
}

export interface EnrollmentFormData {
    class_id: number;
    student_id: number;
    enrolled_at?: string;
}
