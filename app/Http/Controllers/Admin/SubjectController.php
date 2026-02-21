<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\SubjectRepositoryInterface;
use App\Contracts\Services\SubjectServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\Subject;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests;

    public function __construct(
        private readonly SubjectServiceInterface $subjectService,
        private readonly SubjectRepositoryInterface $subjectQueryService
    ) {}

    /**
     * Display a listing of subjects.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Subject::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'level_id']
        );

        $subjects = $this->subjectQueryService->getSubjectsForIndex($selectedYearId, $filters, $perPage);
        $levels = $this->subjectQueryService->getAllLevels();

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'filters' => $filters,
            'levels' => $levels,
            'routeContext' => [
                'role' => 'admin',
                'indexRoute' => 'admin.subjects.index',
                'showRoute' => 'admin.subjects.show',
                'editRoute' => 'admin.subjects.edit',
                'deleteRoute' => 'admin.subjects.destroy',
                'assessmentShowRoute' => null,
            ],
        ]);
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create(): Response
    {
        $this->authorize('create', Subject::class);

        $formData = $this->subjectQueryService->getCreateFormData();

        return Inertia::render('Admin/Subjects/Create', $formData);
    }

    /**
     * Store a newly created subject.
     */
    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $this->subjectService->createSubject($request->validated());

        return redirect()
            ->route('admin.subjects.index')
            ->flashSuccess(__('messages.subject_created'));
    }

    /**
     * Display the specified subject.
     */
    public function show(Request $request, Subject $subject): Response
    {
        $this->authorize('view', $subject);

        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $classSubjectsFilters = [
            'search' => $request->input('search'),
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
        ];

        $data = $this->subjectQueryService->getSubjectDetailsWithPagination(
            $subject,
            $selectedYearId,
            $classSubjectsFilters
        );

        return Inertia::render('Subjects/Show', array_merge($data, [
            'routeContext' => [
                'role' => 'admin',
                'indexRoute' => 'admin.subjects.index',
                'showRoute' => 'admin.subjects.show',
                'editRoute' => 'admin.subjects.edit',
                'deleteRoute' => 'admin.subjects.destroy',
                'assessmentShowRoute' => null,
            ],
        ]));
    }

    /**
     * Show the form for editing the specified subject.
     */
    public function edit(Subject $subject): Response
    {
        $this->authorize('update', $subject);

        $formData = $this->subjectQueryService->getEditFormData($subject);

        return Inertia::render('Admin/Subjects/Edit', $formData);
    }

    /**
     * Update the specified subject.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->subjectService->updateSubject($subject, $request->validated());

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

        if ($this->subjectService->hasClassSubjects($subject)) {
            return back()->flashError(__('messages.subject_has_class_subjects'));
        }

        $this->subjectService->deleteSubject($subject);

        return redirect()
            ->route('admin.subjects.index')
            ->flashSuccess(__('messages.subject_deleted'));
    }
}
