<?php

namespace App\Http\Controllers\Student;

use App\Enums\GradeReportStatus;
use App\Http\Controllers\Controller;
use App\Models\GradeReport;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Student-facing grade report operations (view and download published reports).
 */
class StudentGradeReportController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    /**
     * Display published grade reports for the authenticated student.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GradeReport::class);

        $user = $request->user();
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $reports = GradeReport::where('status', GradeReportStatus::Published)
            ->where('academic_year_id', $selectedYearId)
            ->whereHas('enrollment', fn ($q) => $q->where('student_id', $user->id))
            ->with(['enrollment.class.level', 'semester'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Student/GradeReports/Index', [
            'reports' => $reports,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Show a single published grade report.
     */
    public function show(Request $request, GradeReport $gradeReport): Response
    {
        $this->authorize('view', $gradeReport);

        $gradeReport->load(['enrollment.student', 'enrollment.class.level', 'semester']);

        return Inertia::render('Student/GradeReports/Show', [
            'report' => $gradeReport,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Download the PDF for a published grade report.
     */
    public function download(Request $request, GradeReport $gradeReport): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('download', $gradeReport);

        if (! $gradeReport->file_path || ! Storage::disk('local')->exists($gradeReport->file_path)) {
            return back()->flashError(__('messages.grade_report_pdf_not_found'));
        }

        $gradeReport->loadMissing('enrollment.student');
        $filename = sprintf('bulletin_%s.pdf', str_replace(' ', '_', $gradeReport->enrollment->student->name ?? 'student'));

        return response()->download(
            Storage::disk('local')->path($gradeReport->file_path),
            $filename
        );
    }
}
