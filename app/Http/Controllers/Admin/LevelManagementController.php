<?php

namespace App\Http\Controllers\Admin;

use App\Models\Level;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Requests\LevelRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class LevelManagementController extends Controller
{
    /**
     * Display a listing of levels.
     */
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Level::query()->withCount(['groups', 'activeGroups']);

        // Recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtrer par statut
        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === '1');
        }

        $levels = $query->ordered()->paginate($perPage);

        return Inertia::render('Admin/Levels/Index', [
            'levels' => $levels,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new level.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Levels/Create');
    }

    /**
     * Store a newly created level in storage.
     */
    public function store(LevelRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Level::create($validated);

        // Invalider le cache des groupes car les niveaux sont chargés avec
        Cache::forget('groups_active_with_levels');

        return redirect()
            ->route('levels.index')
            ->with('success', 'Le niveau a été créé avec succès.');
    }

    /**
     * Show the form for editing the specified level.
     */
    public function edit(Level $level): Response
    {
        return Inertia::render('Admin/Levels/Edit', [
            'level' => $level,
        ]);
    }

    /**
     * Update the specified level in storage.
     */
    public function update(LevelRequest $request, Level $level): RedirectResponse
    {
        $validated = $request->validated();

        $level->update($validated);

        // Invalider le cache des groupes
        Cache::forget('groups_active_with_levels');

        return redirect()
            ->route('levels.index')
            ->with('success', 'Le niveau a été modifié avec succès.');
    }

    /**
     * Remove the specified level from storage.
     */
    public function destroy(Level $level): RedirectResponse
    {
        // Vérifier si le niveau a des groupes associés
        if ($level->groups()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer ce niveau car il contient des groupes.');
        }

        $level->delete();

        // Invalider le cache des groupes
        Cache::forget('groups_active_with_levels');

        return redirect()
            ->route('levels.index')
            ->with('success', 'Le niveau a été supprimé avec succès.');
    }

    /**
     * Toggle the active status of the specified level.
     */
    public function toggleStatus(Level $level): RedirectResponse
    {
        $level->update([
            'is_active' => !$level->is_active
        ]);

        // Invalider le cache des groupes
        Cache::forget('groups_active_with_levels');

        $status = $level->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Le niveau a été {$status} avec succès.");
    }
}
