import type { Level } from './shared';
import type { ClassSubject } from './classSubject';

export interface Subject {
    id: number;
    level_id: number;
    name: string;
    code: string;
    description?: string;
    created_at: string;
    updated_at: string;

    level?: Level;
    class_subjects?: ClassSubject[];
    class_subjects_count?: number;
    can_delete?: boolean;
}

export interface SubjectFormData {
    level_id: number;
    name: string;
    code: string;
    description?: string;
}
