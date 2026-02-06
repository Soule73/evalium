<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Http\Requests\Admin\UpdateClassRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\ClassModel;
use App\Services\Admin\ClassQueryService;
use App\Services\Admin\ClassService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HasFlashMessages;

    public function __construct(
        private readonly ClassService $classService,
        private readonly ClassQueryService $classQueryService
    ) {}

    /**
     * Display a listing of classes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassModel::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $filters = $request->only(['search', 'level_id']);
        $perPage = $request->input('per_page', 15);

        $classes = $this->classQueryService->getClassesForIndex(
            $selectedYearId,
            $filters,
            $perPage
        );

        $levels = $this->classQueryService->getAllLevels();

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $classes,
            'filters' => $filters,
            'levels' => $levels,
        ]);
    }

    /**
     * Show the form for creating a new class.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', ClassModel::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $formData = $this->classQueryService->getCreateFormData($selectedYearId);

        return Inertia::render('Admin/Classes/Create', $formData);
    }

    /**
     * Store a newly created class.
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $data = array_merge($request->validated(), [
            'academic_year_id' => $selectedYearId,
        ]);

        $class = $this->classService->createClass($data);

        return redirect()
            ->route('admin.classes.show', $class)
            ->flashSuccess(__('messages.class_created'));
    }

    /**
     * Display the specified class with statistics.
     */
    public function show(Request $request, ClassModel $class): Response
    {
        $this->authorize('view', $class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $this->validateAcademicYearAccess($class, $selectedYearId);

        $studentsFilters = [
            'search' => $request->input('students_search'),
            'page' => $request->input('students_page', 1),
            'per_page' => $request->input('students_per_page', 10),
        ];

        $subjectsFilters = [
            'search' => $request->input('subjects_search'),
            'page' => $request->input('subjects_page', 1),
            'per_page' => $request->input('subjects_per_page', 10),
        ];

        $data = $this->classQueryService->getClassDetailsWithPagination(
            $class,
            $studentsFilters,
            $subjectsFilters
        );

        return Inertia::render('Admin/Classes/Show', $data);
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(ClassModel $class): Response
    {
        $this->authorize('update', $class);

        $formData = $this->classQueryService->getEditFormData($class);

        return Inertia::render('Admin/Classes/Edit', $formData);
    }

    /**
     * Update the specified class.
     */
    public function update(UpdateClassRequest $request, ClassModel $class): RedirectResponse
    {
        $this->classService->updateClass($class, $request->validated());

        return redirect()
            ->route('admin.classes.show', $class)
            ->flashSuccess(__('messages.class_updated'));
    }

    /**
     * Remove the specified class.
     */
    public function destroy(ClassModel $class): RedirectResponse
    {
        $this->authorize('delete', $class);

        try {
            $this->classService->deleteClass($class);

            return redirect()
                ->route('admin.classes.index')
                ->flashSuccess(__('messages.class_deleted'));
        } catch (\InvalidArgumentException $e) {
            return back()->flashError($e->getMessage());
        }
    }
}
