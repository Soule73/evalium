import { z } from 'zod';

export const CreateUserRequestSchema = z
    .object({
        name: z.string().min(2, 'Name must be at least 2 characters').max(255),
        email: z.string().email('Invalid email address').max(255),
        password: z.string().min(8, 'Password must be at least 8 characters'),
        password_confirmation: z.string(),
        roles: z.array(z.string()).optional(),
        is_active: z.boolean().optional().default(true),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export const UpdateUserRequestSchema = z
    .object({
        name: z.string().min(2).max(255).optional(),
        email: z.string().email().max(255).optional(),
        password: z.string().min(8).optional(),
        password_confirmation: z.string().optional(),
        roles: z.array(z.string()).optional(),
        is_active: z.boolean().optional(),
    })
    .refine(
        (data) => {
            if (data.password) {
                return data.password === data.password_confirmation;
            }
            return true;
        },
        {
            message: 'Passwords do not match',
            path: ['password_confirmation'],
        },
    );

export const UpdateProfileRequestSchema = z
    .object({
        name: z.string().min(2).max(255).optional(),
        email: z.string().email().max(255).optional(),
        current_password: z.string().optional(),
        password: z.string().min(8).optional(),
        password_confirmation: z.string().optional(),
    })
    .refine(
        (data) => {
            if (data.password) {
                return !!data.current_password;
            }
            return true;
        },
        {
            message: 'Current password is required when changing password',
            path: ['current_password'],
        },
    )
    .refine(
        (data) => {
            if (data.password) {
                return data.password === data.password_confirmation;
            }
            return true;
        },
        {
            message: 'Passwords do not match',
            path: ['password_confirmation'],
        },
    );

export type CreateUserRequest = z.infer<typeof CreateUserRequestSchema>;
export type UpdateUserRequest = z.infer<typeof UpdateUserRequestSchema>;
export type UpdateProfileRequest = z.infer<typeof UpdateProfileRequestSchema>;
