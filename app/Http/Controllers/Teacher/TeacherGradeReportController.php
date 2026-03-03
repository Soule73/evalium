<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\GradeReport;
use App\Models\Semester;
use App\Services\Core\GradeReport\GradeReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Teacher-facing grade report operations (view and update subject remarks).
 */
class TeacherGradeReportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly GradeReportService $gradeReportService
    ) {}

    /**
     * Display grade reports for a class where the teacher has assigned subjects.
     */
    public function index(Request $request, ClassModel $class): Response
    {
        $this->authorize('viewAny', GradeReport::class);

        $user = $request->user();

        $semesterId = $request->query('semester_id');
        $semester = $semesterId ? Semester::find($semesterId) : null;

        $teacherClassSubjectIds = $class->classSubjects()
            ->where('teacher_id', $user->id)
            ->pluck('id');

        $query = GradeReport::where('academic_year_id', $class->academic_year_id)
            ->whereHas('enrollment', fn ($q) => $q->where('class_id', $class->id));

        if ($semester) {
            $query->where('semester_id', $semester->id);
        }

        $reports = $query->with(['enrollment.student', 'semester'])
            ->orderByDesc('average')
            ->get();

        $semesters = $class->academicYear?->semesters ?? collect();

        return Inertia::render('Teacher/GradeReports/Index', [
            'class' => $class->load('level', 'academicYear'),
            'reports' => $reports,
            'semesters' => $semesters,
            'selectedSemesterId' => $semesterId,
            'teacherClassSubjectIds' => $teacherClassSubjectIds,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Show a single grade report detail for the teacher.
     */
    public function show(Request $request, GradeReport $gradeReport): Response
    {
        $this->authorize('view', $gradeReport);

        $user = $request->user();

        $gradeReport->load(['enrollment.student', 'enrollment.class.level', 'semester']);

        $teacherClassSubjectIds = $gradeReport->enrollment?->class?->classSubjects()
            ->where('teacher_id', $user->id)
            ->pluck('id') ?? collect();

        return Inertia::render('Teacher/GradeReports/Show', [
            'report' => $gradeReport,
            'teacherClassSubjectIds' => $teacherClassSubjectIds,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Update subject remarks on a draft grade report.
     */
    public function updateRemarks(Request $request, GradeReport $gradeReport): RedirectResponse
    {
        $this->authorize('updateRemarks', $gradeReport);

        $validated = $request->validate([
            'subject_remarks' => ['required', 'array', 'min:1'],
            'subject_remarks.*.class_subject_id' => ['required', 'integer'],
            'subject_remarks.*.remark' => ['required', 'string', 'max:255'],
        ]);

        $this->gradeReportService->updateRemarks($gradeReport, $validated['subject_remarks']);

        return back()->flashSuccess(__('messages.subject_remarks_updated'));
    }
}
