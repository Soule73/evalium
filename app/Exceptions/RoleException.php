<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Role Exception
 *
 * Thrown when role or permission operations fail due to business rule violations
 */
class RoleException extends Exception
{
    /**
     * Create exception for when a system role cannot be deleted
     */
    public static function isSystem(): self
    {
        return new self(__('messages.role_cannot_delete_system'));
    }

    /**
     * Create exception for when a system role cannot be renamed
     */
    public static function cannotRenameSystem(): self
    {
        return new self(__('messages.role_cannot_rename_system'));
    }

    /**
     * Create exception for when a role is assigned to users
     */
    public static function hasUsers(): self
    {
        return new self(__('messages.role_cannot_delete_assigned'));
    }

    /**
     * Create exception for when a permission is assigned to roles
     */
    public static function permissionAssignedToRoles(): self
    {
        return new self(__('messages.permission_cannot_delete_assigned'));
    }

    /**
     * Create exception for when role permissions are locked and cannot be modified
     */
    public static function permissionsLocked(): self
    {
        return new self(__('messages.role_permissions_locked'));
    }
}
