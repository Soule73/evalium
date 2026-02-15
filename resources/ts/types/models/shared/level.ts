
export interface Level {
  id: number;
  name: string;
  code: string;
  description?: string;
  order: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;

  classes_count?: number;
  subjects_count?: number;
}
