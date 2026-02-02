import type { Group } from '../legacy';

export interface Level {
  id: number;
  name: string;
  code: string;
  description?: string;
  order: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;

  groups_count?: number;
  groups?: Group[];

  classes_count?: number;
  subjects_count?: number;
}
