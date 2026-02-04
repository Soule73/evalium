<?php

declare(strict_types=1);

return [
    // Title and navigation
    'title' => 'Users',
    'list' => 'User list',
    'manage' => 'Manage users',
    'profile' => 'Profile',

    // Roles
    'student' => 'Student',
    'teacher' => 'Teacher',
    'admin' => 'Administrator',
    'super_admin' => 'Super Admin',
    'unknown' => 'Unknown',
    'students' => 'Students',
    'teachers' => 'Teachers',
    'role' => 'Role',

    // CRUD actions
    'create' => 'Create user',
    'edit' => 'Edit user',
    'delete' => 'Delete user',
    'view' => 'View user',
    'restore' => 'Restore user',
    'force_delete' => 'Permanently delete',

    // Success messages
    'created' => 'User created successfully!',
    'updated' => 'User updated successfully!',
    'deleted' => 'User deleted successfully!',
    'restored' => 'User restored successfully!',
    'force_deleted' => 'User permanently deleted!',
    'status_toggled' => 'User status changed successfully!',

    // Error messages
    'not_found' => 'User not found',
    'unauthorized' => 'You are not authorized to manage this user',
    'cannot_delete_self' => 'You cannot delete your own account',
    'cannot_delete_admin' => 'Cannot delete an administrator',
    'email_exists' => 'This email address is already in use',

    // Status
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'toggle_status' => 'Toggle status',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',

    // Information
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'change_group' => 'Change group',
    'no_group' => 'No group',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',
    'deleted_at' => 'Deleted at',

    // Filters
    'filter_by_role' => 'Filter by role',
    'filter_by_status' => 'Filter by status',
    'filter_by_group' => 'Filter by group',
    'all_users' => 'All users',
    'active_only' => 'Active only',
    'inactive_only' => 'Inactive only',
];
