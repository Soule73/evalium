<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\ClassServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\StoreAcademicYearWizardRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\Admin\AcademicYearService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AcademicYearService $academicYearService,
        private readonly ClassServiceInterface $classService
    ) {}

    /**
     * Display all academic years.
     */
    public function archives(Request $request): Response
    {
        $this->authorize('viewAny', AcademicYear::class);

        $filters = $request->only(['search', 'is_current']);
        $perPage = $request->input('per_page', 20);

        $academicYears = $this->academicYearService->getAcademicYearsForArchives($filters, $perPage);

        return Inertia::render('Admin/AcademicYears/Archives', [
            'academicYears' => $academicYears,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the wizard form for creating a new academic year.
     */
    public function create(): Response
    {
        $this->authorize('create', AcademicYear::class);

        $currentYear = AcademicYear::current()->with(['semesters', 'classes.level'])->first();

        $futureYearExists = $currentYear
            ? AcademicYear::where('start_date', '>', $currentYear->end_date)->exists()
            : false;

        return Inertia::render('Admin/AcademicYears/Create', [
            'currentYear' => $currentYear,
            'futureYearExists' => $futureYearExists,
        ]);
    }

    /**
     * Store a newly created academic year (simple form, non-wizard).
     */
    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $this->academicYearService->createNewYear($request->validated());

        return redirect()
            ->route('admin.academic-years.archives')
            ->flashSuccess(__('messages.academic_year_created'));
    }

    /**
     * Store a new academic year via the multi-step wizard,
     * optionally duplicating classes from the current year.
     */
    public function storeWizard(StoreAcademicYearWizardRequest $request): JsonResponse
    {
        $currentYear = AcademicYear::current()->first();

        if ($currentYear && AcademicYear::where('start_date', '>', $currentYear->end_date)->exists()) {
            return response()->json(
                ['message' => __('messages.future_year_already_exists')],
                422
            );
        }

        $validated = $request->validated();
        $classIds = $validated['class_ids'] ?? [];
        unset($validated['class_ids']);

        $newYear = $this->academicYearService->createNewYear($validated);

        $duplicatedCount = 0;
        if ($currentYear && ! empty($classIds)) {
            $duplicated = $this->classService->duplicateClassesToNewYear($currentYear, $newYear, $classIds);
            $duplicatedCount = $duplicated->count();
        }

        return response()->json([
            'year' => $newYear->load('semesters'),
            'duplicated_classes_count' => $duplicatedCount,
        ], 201);
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(AcademicYear $academicYear): Response
    {
        $this->authorize('update', $academicYear);

        $academicYear->load('semesters');

        return Inertia::render('Admin/AcademicYears/Edit', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->academicYearService->updateYear($academicYear, $request->validated());

        return redirect()
            ->route('admin.academic-years.archives')
            ->flashSuccess(__('messages.academic_year_updated'));
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        try {
            $this->academicYearService->deleteYear($academicYear);

            return redirect()
                ->route('admin.academic-years.archives')
                ->flashSuccess(__('messages.academic_year_deleted'));
        } catch (\InvalidArgumentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Set the specified academic year as current.
     */
    public function setCurrent(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $this->academicYearService->setCurrentYear($academicYear);

        return back()->flashSuccess(__('messages.academic_year_set_current'));
    }

    /**
     * Archive the specified academic year.
     */
    public function archive(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $this->academicYearService->archiveYear($academicYear);

        return back()->flashSuccess(__('messages.academic_year_archived'));
    }
}
