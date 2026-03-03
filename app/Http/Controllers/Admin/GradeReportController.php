<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GradeReportStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\GradeReport;
use App\Models\Semester;
use App\Services\Core\GradeReport\GradeReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Admin-facing grade report management (generate, validate, publish, download).
 */
class GradeReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly GradeReportService $gradeReportService
    ) {}

    /**
     * Display grade reports listing for a given class.
     */
    public function index(Request $request, ClassModel $class): Response
    {
        $this->authorize('viewAny', GradeReport::class);

        $semesterId = $request->query('semester_id');
        $semester = $semesterId ? Semester::find($semesterId) : null;

        $query = GradeReport::where('academic_year_id', $class->academic_year_id)
            ->whereHas('enrollment', fn ($q) => $q->where('class_id', $class->id));

        if ($semester) {
            $query->where('semester_id', $semester->id);
        }

        $reports = $query->with(['enrollment.student', 'semester', 'validator'])
            ->orderByDesc('average')
            ->get();

        $semesters = $class->academicYear?->semesters ?? collect();

        return Inertia::render('Admin/GradeReports/Index', [
            'class' => $class->load('level', 'academicYear'),
            'reports' => $reports,
            'semesters' => $semesters,
            'selectedSemesterId' => $semesterId,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Show a single grade report detail.
     */
    public function show(Request $request, GradeReport $gradeReport): Response
    {
        $this->authorize('view', $gradeReport);

        $gradeReport->load(['enrollment.student', 'enrollment.class.level', 'semester', 'validator']);

        return Inertia::render('Admin/GradeReports/Show', [
            'report' => $gradeReport,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Generate draft grade reports for all active enrollments in a class.
     */
    public function generate(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', GradeReport::class);

        $semesterId = $request->input('semester_id');
        $semester = $semesterId ? Semester::findOrFail($semesterId) : null;

        $reports = $this->gradeReportService->generateDrafts($class, $semester);

        return back()->flashSuccess(
            __('messages.grade_reports_generated', ['count' => $reports->count()])
        );
    }

    /**
     * Update the general remark on a grade report.
     */
    public function updateGeneralRemark(Request $request, GradeReport $gradeReport): RedirectResponse
    {
        $this->authorize('updateGeneralRemark', $gradeReport);

        $validated = $request->validate([
            'general_remark' => ['required', 'string', 'max:500'],
        ]);

        $this->gradeReportService->updateGeneralRemark($gradeReport, $validated['general_remark']);

        return back()->flashSuccess(__('messages.general_remark_updated'));
    }

    /**
     * Validate a single grade report and generate its PDF.
     */
    public function validateReport(Request $request, GradeReport $gradeReport): RedirectResponse
    {
        $this->authorize('validate', $gradeReport);

        $this->gradeReportService->validate($gradeReport, $request->user());

        return back()->flashSuccess(__('messages.grade_report_validated'));
    }

    /**
     * Validate all draft reports for a class in batch.
     */
    public function validateBatch(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('validateBatch', GradeReport::class);

        $semesterId = $request->input('semester_id');
        $semester = $semesterId ? Semester::findOrFail($semesterId) : null;

        $count = $this->gradeReportService->validateBatch($class, $semester, $request->user());

        return back()->flashSuccess(
            __('messages.grade_reports_validated_batch', ['count' => $count])
        );
    }

    /**
     * Publish a validated report making it visible to the student.
     */
    public function publish(Request $request, GradeReport $gradeReport): RedirectResponse
    {
        $this->authorize('publish', $gradeReport);

        $this->gradeReportService->publish($gradeReport);

        return back()->flashSuccess(__('messages.grade_report_published'));
    }

    /**
     * Publish all validated reports for a class in batch.
     */
    public function publishBatch(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('publishBatch', GradeReport::class);

        $semesterId = $request->input('semester_id');
        $semester = $semesterId ? Semester::findOrFail($semesterId) : null;

        $query = GradeReport::where('academic_year_id', $class->academic_year_id)
            ->where('status', GradeReportStatus::Validated)
            ->whereHas('enrollment', fn ($q) => $q->where('class_id', $class->id));

        if ($semester) {
            $query->where('semester_id', $semester->id);
        } else {
            $query->whereNull('semester_id');
        }

        $reports = $query->get();

        foreach ($reports as $report) {
            $this->gradeReportService->publish($report);
        }

        return back()->flashSuccess(
            __('messages.grade_reports_published_batch', ['count' => $reports->count()])
        );
    }

    /**
     * Preview (inline) the PDF for a grade report without persisting.
     */
    public function preview(GradeReport $gradeReport): HttpResponse
    {
        $this->authorize('download', $gradeReport);

        $content = $this->gradeReportService->renderPdfContent($gradeReport);

        $gradeReport->loadMissing('enrollment.student');
        $filename = sprintf('bulletin_%s.pdf', str_replace(' ', '_', $gradeReport->enrollment->student->name ?? 'student'));

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Download the PDF for a single grade report.
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

    /**
     * Download a ZIP archive of all reports for a class.
     */
    public function downloadBatch(Request $request, ClassModel $class): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('downloadBatch', GradeReport::class);

        $semesterId = $request->query('semester_id');
        $semester = $semesterId ? Semester::findOrFail($semesterId) : null;

        $zipPath = $this->gradeReportService->generateBatchPdf($class, $semester);

        if (! Storage::disk('local')->exists($zipPath)) {
            return back()->flashError(__('messages.grade_report_pdf_not_found'));
        }

        $filename = sprintf('bulletins_%s.zip', str_replace(' ', '_', $class->name));

        return response()->download(
            Storage::disk('local')->path($zipPath),
            $filename
        );
    }
}
