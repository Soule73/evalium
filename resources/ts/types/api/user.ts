import { type User } from '../index';
import { type PaginatedResponse, type ApiResponse } from './common';

export interface GetUsersResponse extends PaginatedResponse<User> {}

export interface GetUserResponse extends ApiResponse<User> {}

export interface CreateUserRequest {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    roles?: string[];
    is_active?: boolean;
}

export interface UpdateUserRequest {
    name?: string;
    email?: string;
    password?: string;
    password_confirmation?: string;
    roles?: string[];
    is_active?: boolean;
}

export interface UpdateProfileRequest {
    name?: string;
    email?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
}

export interface BulkDeleteUsersRequest {
    user_ids: number[];
}
