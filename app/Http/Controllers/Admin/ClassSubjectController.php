<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\ClassSubjectRepositoryInterface;
use App\Contracts\Services\ClassSubjectServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplaceTeacherRequest;
use App\Http\Requests\Admin\StoreClassSubjectRequest;
use App\Models\ClassSubject;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassSubjectController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear;

    public function __construct(
        private readonly ClassSubjectServiceInterface $classSubjectService,
        private readonly ClassSubjectRepositoryInterface $classSubjectQueryService
    ) {}

    /**
     * Display a listing of class subjects (teacher-subject-class assignments).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassSubject::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $filters = $request->only(['search', 'class_id', 'subject_id', 'teacher_id', 'active_only']);
        $perPage = $request->input('per_page', 15);
        $activeOnly = ($filters['active_only'] ?? true) === true;

        $classSubjects = $this->classSubjectQueryService->getClassSubjectsForIndex(
            $selectedYearId,
            $filters,
            $activeOnly,
            $perPage
        );

        $formData = $this->classSubjectService->getFormDataForCreate($selectedYearId);

        return Inertia::render('Admin/ClassSubjects/Index', [
            'classSubjects' => $classSubjects,
            'filters' => $filters,
            'formData' => $formData,
        ]);
    }

    /**
     * Store a newly created class subject assignment.
     */
    public function store(StoreClassSubjectRequest $request): RedirectResponse
    {
        try {
            $classSubject = $this->classSubjectService->assignTeacherToClassSubject($request->validated());

            $redirectTo = $request->input('redirect_to');
            if ($redirectTo && str_starts_with($redirectTo, '/')) {
                return redirect($redirectTo)->flashSuccess(__('messages.class_subject_created'));
            }

            return redirect()
                ->route('admin.classes.subjects.show', [
                    'class' => $classSubject->class_id,
                    'class_subject' => $classSubject->id,
                ])
                ->flashSuccess(__('messages.class_subject_created'));
        } catch (\InvalidArgumentException $e) {
            return back()->flashError($e->getMessage());
        }
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
        $data = $this->classSubjectQueryService->getClassAndSubjectForHistory($classId, $subjectId);

        return Inertia::render('Admin/ClassSubjects/History', [
            'history' => $history,
            'class' => $data['class'],
            'subject' => $data['subject'],
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
                ->route('admin.classes.subjects.show', [
                    'class' => $newClassSubject->class_id,
                    'class_subject' => $newClassSubject->id,
                ])
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
