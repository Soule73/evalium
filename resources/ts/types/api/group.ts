import { Group } from '../index';
import { PaginatedResponse, ApiResponse } from './common';

export interface GetGroupsResponse extends PaginatedResponse<Group> { }

export interface GetGroupResponse extends ApiResponse<Group> { }

export interface CreateGroupRequest {
    display_name: string;
    description?: string;
    level_id?: number | null;
    start_date: string;
    end_date: string;
    max_students: number;
    is_active: boolean;
    academic_year?: string;
}

export interface UpdateGroupRequest extends Partial<CreateGroupRequest> { }

export interface AssignStudentsToGroupRequest {
    student_ids: number[];
}

export interface RemoveStudentsFromGroupRequest {
    student_ids: number[];
}
