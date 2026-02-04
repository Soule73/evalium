import type { Role } from './role';
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
  permissions?: string[];

  current_enrollment?: Enrollment;
  enrollments?: Enrollment[];
  classes?: ClassModel[];
}

