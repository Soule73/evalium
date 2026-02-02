<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Http\Requests\Admin\UpdateClassRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Level;
use App\Services\Admin\ClassService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly ClassService $classService
    ) {}

    /**
     * Display a listing of classes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassModel::class);

        $filters = $request->only(['search', 'level_id', 'academic_year_id']);
        $perPage = $request->input('per_page', 15);

        $classes = ClassModel::query()
            ->with(['academicYear', 'level', 'students'])
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['level_id'] ?? null, fn($query, $levelId) => $query->where('level_id', $levelId))
            ->when($filters['academic_year_id'] ?? null, fn($query, $yearId) => $query->where('academic_year_id', $yearId))
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('level_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $levels = Level::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $classes,
            'filters' => $filters,
            'levels' => $levels,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Show the form for creating a new class.
     */
    public function create(): Response
    {
        $this->authorize('create', ClassModel::class);

        $levels = Level::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Classes/Create', [
            'levels' => $levels,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created class.
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $class = $this->classService->createClass($request->validated());

        return redirect()
            ->route('admin.classes.show', $class)
            ->flashSuccess(__('messages.class_created'));
    }

    /**
     * Display the specified class with statistics.
     */
    public function show(ClassModel $class): Response
    {
        $this->authorize('view', $class);

        $class->load([
            'academicYear',
            'level',
            'enrollments.student',
            'classSubjects.subject',
            'classSubjects.teacher',
            'classSubjects.assessments',
        ]);

        $statistics = $this->classService->getClassStatistics($class);

        return Inertia::render('Admin/Classes/Show', [
            'class' => $class,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(ClassModel $class): Response
    {
        $this->authorize('update', $class);

        $levels = Level::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return Inertia::render('Admin/Classes/Edit', [
            'class' => $class->load(['academicYear', 'level']),
            'levels' => $levels,
            'academicYears' => $academicYears,
        ]);
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
