import type { Role } from './role';
import type { Group } from '../legacy';
import type { Enrollment } from '../mcd/enrollment';
import type { ClassModel } from '../mcd/class';

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

  current_group?: Group;
  groups?: GroupWithPivot[];

  current_enrollment?: Enrollment;
  enrollments?: Enrollment[];
  classes?: ClassModel[];
}

export interface GroupWithPivot extends Group {
  pivot?: {
    enrolled_at: string;
    left_at?: string | null;
    is_active: boolean;
  };
}
