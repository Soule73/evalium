<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Services\Admin\AcademicYearService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AcademicYearService $academicYearService
    ) {}

    /**
     * Display a listing of academic years.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AcademicYear::class);

        $filters = $request->only(['search', 'is_current']);
        $perPage = $request->input('per_page', 15);

        $academicYears = $this->academicYearService->getAcademicYearsForIndex($filters, $perPage);

        return Inertia::render('Admin/AcademicYears/Index', [
            'academicYears' => $academicYears,
            'filters' => $filters,
        ]);
    }

    /**
     * Display all academic years in archives view.
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
     * Show the form for creating a new academic year.
     */
    public function create(): Response
    {
        $this->authorize('create', AcademicYear::class);

        return Inertia::render('Admin/AcademicYears/Create');
    }

    /**
     * Store a newly created academic year.
     */
    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $academicYear = $this->academicYearService->createNewYear($request->validated());

        return redirect()
            ->route('admin.academic-years.show', $academicYear)
            ->flashSuccess(__('messages.academic_year_created'));
    }

    /**
     * Display the specified academic year.
     */
    public function show(AcademicYear $academicYear): Response
    {
        $this->authorize('view', $academicYear);

        $academicYear = $this->academicYearService->loadAcademicYearDetails($academicYear);

        return Inertia::render('Admin/AcademicYears/Show', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(AcademicYear $academicYear): Response
    {
        $this->authorize('update', $academicYear);

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
            ->route('admin.academic-years.show', $academicYear)
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
                ->route('admin.academic-years.index')
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
