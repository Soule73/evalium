<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Repositories\AdminAssessmentRepositoryInterface;
use App\Contracts\Repositories\ClassRepositoryInterface;
use App\Contracts\Repositories\ClassSubjectRepositoryInterface;
use App\Contracts\Repositories\TeacherAssessmentRepositoryInterface;
use App\Contracts\Services\ClassServiceInterface;
use App\Contracts\Services\ClassSubjectServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassRequest;
use App\Http\Requests\Admin\UpdateClassRequest;
use App\Http\Traits\HandlesIndexRequests;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\User;
use App\Repositories\Teacher\GradingRepository;
use App\Traits\FiltersAcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    use AuthorizesRequests, FiltersAcademicYear, HandlesIndexRequests;

    public function __construct(
        private readonly ClassServiceInterface $classService,
        private readonly ClassRepositoryInterface $classQueryService,
        private readonly AdminAssessmentRepositoryInterface $assessmentQueryService,
        private readonly ClassSubjectServiceInterface $classSubjectService,
        private readonly ClassSubjectRepositoryInterface $classSubjectQueryService,
        private readonly TeacherAssessmentRepositoryInterface $teacherAssessmentQueryService,
        private readonly GradingRepository $gradingQueryService
    ) {}

    /**
     * Display a listing of classes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassModel::class);

        $selectedYearId = $this->getSelectedAcademicYearId($request);
        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'level_id']
        );

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
        $formData = $this->classService->getCreateFormData($selectedYearId);

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

        $data = $this->classQueryService->getClassDetailsWithPagination(
            $class,
            $studentsFilters,
            ['search' => null, 'page' => 1, 'per_page' => 5]
        );

        $data['recentClassSubjects'] = $data['classSubjects'];
        unset($data['classSubjects'], $data['subjectsFilters']);

        $data['recentAssessments'] = $this->assessmentQueryService->getAssessmentsForClass($class, [], 3);
        $data['classSubjectFormData'] = $this->classSubjectService->getFormDataForCreate($selectedYearId);

        return Inertia::render('Admin/Classes/Show', $data);
    }

    /**
     * Display the full paginated list of subject assignments for a class with filters.
     */
    public function classSubjectsList(Request $request, ClassModel $class): Response
    {
        $this->authorize('view', $class);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'teacher_id', 'active_only']
        );

        $classSubjects = $this->classQueryService->getPaginatedClassSubjects(
            $class,
            array_merge($filters, ['per_page' => $perPage, 'page' => $request->input('page', 1)])
        );

        $teachers = User::query()
            ->join('class_subjects', 'class_subjects.teacher_id', '=', 'users.id')
            ->where('class_subjects.class_id', $class->id)
            ->distinct()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);

        $selectedYearId = $this->getSelectedAcademicYearId($request);

        return Inertia::render('Admin/Classes/Subjects', [
            'class' => $class->load('level', 'academicYear'),
            'classSubjects' => $classSubjects,
            'filters' => $filters,
            'teachers' => $teachers,
            'classSubjectFormData' => $this->classSubjectService->getFormDataForCreate($selectedYearId),
        ]);
    }

    /**
     * Display the full paginated list of assessments for a class with filters.
     */
    public function classAssessments(Request $request, ClassModel $class): Response
    {
        $this->authorize('view', $class);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'subject_id', 'teacher_id']
        );

        $assessments = $this->assessmentQueryService->getAssessmentsForClass(
            $class,
            $filters,
            $perPage
        );

        $subjects = Subject::query()
            ->join('class_subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->where('class_subjects.class_id', $class->id)
            ->whereNull('class_subjects.valid_to')
            ->distinct()
            ->orderBy('subjects.name')
            ->get(['subjects.id', 'subjects.name']);

        $teachers = User::query()
            ->join('class_subjects', 'class_subjects.teacher_id', '=', 'users.id')
            ->where('class_subjects.class_id', $class->id)
            ->whereNull('class_subjects.valid_to')
            ->distinct()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);

        return Inertia::render('Admin/Classes/Assessments', [
            'class' => $class->load('level', 'academicYear'),
            'assessments' => $assessments,
            'filters' => $filters,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Display the specified assessment within its class context.
     */
    public function assessmentShow(Request $request, ClassModel $class, Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $perPage = (int) $request->input('per_page', 10);
        $assessment = $this->teacherAssessmentQueryService->loadAssessmentDetails($assessment);

        $assignments = $this->gradingQueryService->getAssignmentsWithEnrolledStudents(
            $assessment,
            $request->only(['search']),
            $perPage
        );

        $routeContext = [
            'role' => 'admin',
            'backRoute' => 'admin.assessments.index',
            'showRoute' => null,
            'reviewRoute' => 'admin.assessments.review',
            'gradeRoute' => 'admin.assessments.grade',
            'saveGradeRoute' => 'admin.assessments.saveGrade',
            'editRoute' => null,
            'publishRoute' => null,
            'unpublishRoute' => null,
            'duplicateRoute' => null,
            'reopenRoute' => null,
        ];

        return Inertia::render('Assessments/Show', [
            'assessment' => $assessment,
            'assignments' => $assignments,
            'routeContext' => $routeContext,
        ]);
    }

    /**
     * Display the specified class subject assignment within its class context.
     */
    public function subjectShow(Request $request, ClassModel $class, ClassSubject $class_subject): Response
    {
        $this->authorize('view', $class_subject);

        $class_subject = $this->classSubjectQueryService->loadClassSubjectDetails($class_subject);
        $teachers = $this->classSubjectQueryService->getTeachersForReplacement();
        $history = $this->classSubjectQueryService->getPaginatedHistory(
            $class_subject->class_id,
            $class_subject->subject_id,
            $request->input('history_per_page', 10),
            $class_subject->id
        );

        return Inertia::render('Admin/Classes/SubjectShow', [
            'class' => $class_subject->class,
            'classSubject' => $class_subject,
            'teachers' => $teachers,
            'history' => $history,
        ]);
    }

    /**
     * Show the form for editing the specified class.
     */
    public function edit(ClassModel $class): Response
    {
        $this->authorize('update', $class);

        $formData = $this->classService->getEditFormData($class);

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
