<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LevelRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\Level;
use App\Services\Admin\LevelService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LevelController extends Controller
{
    use AuthorizesRequests, HandlesIndexRequests;

    public function __construct(
        private readonly LevelService $levelService
    ) {}

    /**
     * Display a listing of levels with filters.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return Response The response containing the levels list view.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Level::class);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'status']
        );
        $filters['per_page'] = $perPage;

        $levels = $this->levelService->getLevelsWithPagination($filters);

        return Inertia::render('Admin/Levels/Index', [
            'levels' => $levels,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new level.
     *
     * @return Response The response containing the level creation form view.
     */
    public function create(): Response
    {
        $this->authorize('create', Level::class);

        return Inertia::render('Admin/Levels/Create');
    }

    /**
     * Store a newly created level.
     *
     * Delegates to LevelService to create level with validated data.
     *
     * @param  LevelRequest  $request  The validated request containing level data.
     * @return RedirectResponse Redirects to levels index on success.
     */
    public function store(LevelRequest $request): RedirectResponse
    {
        try {
            $this->levelService->createLevel($request->validated());

            return redirect()->route('admin.levels.index')->flashSuccess(
                __('messages.level_created')
            );
        } catch (\Exception $e) {
            Log::error('Error creating level', ['error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Show the form for editing the specified level.
     *
     * @param  Level  $level  The level to edit.
     * @return Response The response containing the level edit form view.
     */
    public function edit(Level $level): Response
    {
        $this->authorize('update', $level);

        return Inertia::render('Admin/Levels/Edit', [
            'level' => $level,
        ]);
    }

    /**
     * Update the specified level.
     *
     * Delegates to LevelService to update level with validated data.
     *
     * @param  LevelRequest  $request  The validated request containing updated level data.
     * @param  Level  $level  The level to update.
     * @return RedirectResponse Redirects to levels index on success.
     */
    public function update(LevelRequest $request, Level $level): RedirectResponse
    {
        try {
            $this->levelService->updateLevel($level, $request->validated());

            return redirect()->route('admin.levels.index')->flashSuccess(
                __('messages.level_updated')
            );
        } catch (\Exception $e) {
            Log::error('Error updating level', ['level_id' => $level->id, 'error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }

    /**
     * Remove the specified level from storage.
     *
     * Delegates to LevelService to delete level (validates no groups are associated).
     *
     * @param  Level  $level  The level to delete.
     * @return RedirectResponse Redirects to levels index on success.
     */
    public function destroy(Level $level): RedirectResponse
    {
        $this->authorize('delete', $level);

        try {
            $this->levelService->deleteLevel($level);

            return redirect()->route('admin.levels.index')->flashSuccess(
                __('messages.level_deleted')
            );
        } catch (\Exception $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Toggle the active status of the specified level.
     *
     * Delegates to LevelService to toggle level status.
     *
     * @param  Level  $level  The level to toggle.
     * @return RedirectResponse Redirects back with success message.
     */
    public function toggleStatus(Level $level): RedirectResponse
    {
        $this->authorize('update', $level);

        try {
            $level = $this->levelService->toggleStatus($level);

            $messageKey = $level->is_active ? 'messages.level_activated' : 'messages.level_deactivated';

            return back()->flashSuccess(__($messageKey));
        } catch (\Exception $e) {
            Log::error('Error toggling level status', ['level_id' => $level->id, 'error' => $e->getMessage()]);

            return back()->flashError(__('messages.operation_failed'));
        }
    }
}
