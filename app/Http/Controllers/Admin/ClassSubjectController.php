<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceTeacherRequest;
use App\Http\Requests\Admin\StoreClassSubjectRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\Core\ClassSubjectService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassSubjectController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HasFlashMessages;

    public function __construct(
        private readonly ClassSubjectService $classSubjectService
    ) {}

    /**
     * Display a listing of class subjects (teacher-subject-class assignments).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassSubject::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $filters = $request->only(['class_id', 'subject_id', 'teacher_id', 'active_only']);
        $perPage = $request->input('per_page', 15);
        $activeOnly = ($filters['active_only'] ?? true) === true;

        $classSubjects = ClassSubject::query()
            ->forAcademicYear($selectedYearId)
            ->with(['class.academicYear', 'class.level', 'subject', 'teacher', 'semester'])
            ->when($filters['class_id'] ?? null, fn ($query, $classId) => $query->where('class_id', $classId))
            ->when($filters['subject_id'] ?? null, fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacherId) => $query->where('teacher_id', $teacherId))
            ->when($activeOnly, fn ($query) => $query->active())
            ->orderBy('valid_from', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $classes = ClassModel::forAcademicYear($selectedYearId)
            ->with('academicYear')
            ->orderBy('name')
            ->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::role('teacher')->orderBy('name')->get();

        return Inertia::render('Admin/ClassSubjects/Index', [
            'classSubjects' => $classSubjects,
            'filters' => $filters,
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Show the form for creating a new class subject assignment.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', ClassSubject::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);

        $classes = ClassModel::forAcademicYear($selectedYearId)
            ->with('academicYear', 'level')
            ->orderBy('name')
            ->get();
        $subjects = Subject::with('level')->orderBy('name')->get();
        $teachers = User::role('teacher')->orderBy('name')->get();
        $semesters = Semester::where('academic_year_id', $selectedYearId)
            ->with('academicYear')
            ->orderBy('order_number')
            ->get();

        return Inertia::render('Admin/ClassSubjects/Create', [
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'semesters' => $semesters,
        ]);
    }

    /**
     * Store a newly created class subject assignment.
     */
    public function store(StoreClassSubjectRequest $request): RedirectResponse
    {
        try {
            $classSubject = $this->classSubjectService->assignTeacherToClassSubject($request->validated());

            return redirect()
                ->route('admin.class-subjects.show', $classSubject)
                ->flashSuccess(__('messages.class_subject_created'));
        } catch (\InvalidArgumentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Display the specified class subject assignment.
     */
    public function show(ClassSubject $classSubject): Response
    {
        $this->authorize('view', $classSubject);

        $classSubject->load([
            'class.academicYear',
            'class.level',
            'subject',
            'teacher',
            'semester',
            'assessments',
        ]);

        return Inertia::render('Admin/ClassSubjects/Show', [
            'classSubject' => $classSubject,
        ]);
    }

    /**
     * Display teaching history for a specific class-subject combination.
     */
    public function history(Request $request): Response
    {
        $this->authorize('viewAny', ClassSubject::class);

        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');

        if (! $classId || ! $subjectId) {
            abort(400, __('messages.class_and_subject_required'));
        }

        $history = $this->classSubjectService->getTeachingHistory($classId, $subjectId);
        $class = ClassModel::with('academicYear', 'level')->findOrFail($classId);
        $subject = Subject::findOrFail($subjectId);

        return Inertia::render('Admin/ClassSubjects/History', [
            'history' => $history,
            'class' => $class,
            'subject' => $subject,
        ]);
    }

    /**
     * Replace the teacher for a class subject assignment.
     */
    public function replaceTeacher(ReplaceTeacherRequest $request, ClassSubject $classSubject): RedirectResponse
    {
        try {
            $newClassSubject = $this->classSubjectService->replaceTeacher(
                $classSubject,
                $request->input('new_teacher_id'),
                $request->input('effective_date')
            );

            return redirect()
                ->route('admin.class-subjects.show', $newClassSubject)
                ->flashSuccess(__('messages.teacher_replaced'));
        } catch (\InvalidArgumentException $e) {
            return back()->flashError($e->getMessage());
        }
    }

    /**
     * Update the coefficient for a class subject.
     */
    public function updateCoefficient(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('update', $classSubject);

        $request->validate([
            'coefficient' => ['required', 'numeric', 'min:0.01'],
        ]);

        $this->classSubjectService->updateCoefficient($classSubject, $request->input('coefficient'));

        return back()->flashSuccess(__('messages.coefficient_updated'));
    }

    /**
     * Terminate a class subject assignment.
     */
    public function terminate(Request $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('update', $classSubject);

        $request->validate([
            'end_date' => ['required', 'date'],
        ]);

        $this->classSubjectService->terminateAssignment($classSubject, $request->input('end_date'));

        return back()->flashSuccess(__('messages.assignment_terminated'));
    }

    /**
     * Remove the specified class subject assignment.
     */
    public function destroy(ClassSubject $classSubject): RedirectResponse
    {
        $this->authorize('delete', $classSubject);

        if ($classSubject->assessments()->exists()) {
            return back()->flashError(__('messages.class_subject_has_assessments'));
        }

        $classSubject->delete();

        return redirect()
            ->route('admin.class-subjects.index')
            ->flashSuccess(__('messages.class_subject_deleted'));
    }
}
