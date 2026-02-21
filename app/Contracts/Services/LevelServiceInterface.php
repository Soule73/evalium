<?php

namespace App\Contracts\Services;

use App\Models\Level;

interface LevelServiceInterface
{
    /**
     * Create a new level.
     */
    public function createLevel(array $data): Level;

    /**
     * Update an existing level.
     */
    public function updateLevel(Level $level, array $data): Level;

    /**
     * Delete a level.
     */
    public function deleteLevel(Level $level): bool;

    /**
     * Toggle the active status of a level.
     */
    public function toggleStatus(Level $level): Level;
}
