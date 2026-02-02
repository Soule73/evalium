<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Level;
use App\Models\Subject;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    /**
     * Display a listing of subjects.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Subject::class);

        $filters = $request->only(['search', 'level_id']);
        $perPage = $request->input('per_page', 15);

        $subjects = Subject::query()
            ->with('level')
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"))
            ->when($filters['level_id'] ?? null, fn($query, $levelId) => $query->where('level_id', $levelId))
            ->orderBy('level_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $levels = Level::orderBy('name')->get();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => $filters,
            'levels' => $levels,
        ]);
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create(): Response
    {
        $this->authorize('create', Subject::class);

        $levels = Level::orderBy('name')->get();

        return Inertia::render('Admin/Subjects/Create', [
            'levels' => $levels,
        ]);
    }

    /**
     * Store a newly created subject.
     */
    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        return redirect()
            ->route('admin.subjects.index')
            ->flashSuccess(__('messages.subject_created'));
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject): Response
    {
        $this->authorize('view', $subject);

        $subject->load('level', 'classSubjects.class', 'classSubjects.teacher');

        return Inertia::render('Admin/Subjects/Show', [
            'subject' => $subject,
        ]);
    }

    /**
     * Show the form for editing the specified subject.
     */
    public function edit(Subject $subject): Response
    {
        $this->authorize('update', $subject);

        $levels = Level::orderBy('name')->get();

        return Inertia::render('Admin/Subjects/Edit', [
            'subject' => $subject->load('level'),
            'levels' => $levels,
        ]);
    }

    /**
     * Update the specified subject.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->flashSuccess(__('messages.subject_updated'));
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        if ($subject->classSubjects()->exists()) {
            return back()->flashError(__('messages.subject_has_class_subjects'));
        }

        $subject->delete();

        return redirect()
            ->route('admin.subjects.index')
            ->flashSuccess(__('messages.subject_deleted'));
    }
}
