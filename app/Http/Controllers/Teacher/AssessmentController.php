<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreAssessmentRequest;
use App\Http\Requests\Teacher\UpdateAssessmentRequest;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Services\Core\AssessmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly AssessmentService $assessmentService
    ) {}

    /**
     * Display a listing of teacher's assessments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Assessment::class);

        $filters = $request->only(['search', 'class_subject_id', 'type', 'is_published']);
        $perPage = $request->input('per_page', 15);

        $teacherId = $request->user()->id;

        $assessments = Assessment::query()
            ->with(['classSubject.class', 'classSubject.subject', 'questions'])
            ->whereHas('classSubject', fn($query) => $query->where('teacher_id', $teacherId))
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['class_subject_id'] ?? null, fn($query, $classSubjectId) => $query->where('class_subject_id', $classSubjectId))
            ->when($filters['type'] ?? null, fn($query, $type) => $query->where('type', $type))
            ->when(isset($filters['is_published']), fn($query) => $query->where('is_published', (bool) $filters['is_published']))
            ->orderBy('scheduled_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->with(['class', 'subject'])
            ->active()
            ->get();

        return Inertia::render('Teacher/Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $filters,
            'classSubjects' => $classSubjects,
        ]);
    }

    /**
     * Show the form for creating a new assessment.
     */
    public function create(): Response
    {
        $this->authorize('create', Assessment::class);

        $teacherId = auth()->id();

        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->with(['class.academicYear', 'class.level', 'subject'])
            ->active()
            ->get();

        return Inertia::render('Teacher/Assessments/Create', [
            'classSubjects' => $classSubjects,
        ]);
    }

    /**
     * Store a newly created assessment.
     */
    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $assessment = $this->assessmentService->createAssessment($request->validated());

        return redirect()
            ->route('teacher.assessments.show', $assessment)
            ->flashSuccess(__('messages.assessment_created'));
    }

    /**
     * Display the specified assessment.
     */
    public function show(Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $assessment->load([
            'classSubject.class.academicYear',
            'classSubject.subject',
            'classSubject.teacher',
            'questions.choices',
            'assignments.student',
        ]);

        return Inertia::render('Teacher/Assessments/Show', [
            'assessment' => $assessment,
        ]);
    }

    /**
     * Show the form for editing the specified assessment.
     */
    public function edit(Assessment $assessment): Response
    {
        $this->authorize('update', $assessment);

        $assessment->load([
            'classSubject.class',
            'classSubject.subject',
            'questions.choices',
        ]);

        return Inertia::render('Teacher/Assessments/Edit', [
            'assessment' => $assessment,
        ]);
    }

    /**
     * Update the specified assessment.
     */
    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->assessmentService->updateAssessment($assessment, $request->validated());

        return redirect()
            ->route('teacher.assessments.show', $assessment)
            ->flashSuccess(__('messages.assessment_updated'));
    }

    /**
     * Remove the specified assessment.
     */
    public function destroy(Assessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);

        $this->assessmentService->deleteAssessment($assessment);

        return redirect()
            ->route('teacher.assessments.index')
            ->flashSuccess(__('messages.assessment_deleted'));
    }

    /**
     * Publish the specified assessment.
     */
    public function publish(Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $this->assessmentService->publishAssessment($assessment);

        return back()->flashSuccess(__('messages.assessment_published'));
    }

    /**
     * Unpublish the specified assessment.
     */
    public function unpublish(Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $this->assessmentService->unpublishAssessment($assessment);

        return back()->flashSuccess(__('messages.assessment_unpublished'));
    }

    /**
     * Duplicate the specified assessment.
     */
    public function duplicate(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $overrides = $request->only(['title', 'scheduled_date']);

        $newAssessment = $this->assessmentService->duplicateAssessment($assessment, $overrides);

        return redirect()
            ->route('teacher.assessments.show', $newAssessment)
            ->flashSuccess(__('messages.assessment_duplicated'));
    }
}
