<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Services\Core\ClassSubjectService;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherClassController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly ClassSubjectService $classSubjectService
    ) {}

    /**
     * Display all classes where the teacher is assigned.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['search', 'level_id', 'academic_year_id']);

        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->forAcademicYear($selectedYearId)
            ->active()
            ->with([
                'class.academicYear',
                'class.level',
                'class.students',
                'subject',
                'assessments',
            ])
            ->get();

        $classes = $classSubjects->unique('class_id')->pluck('class')->values();

        if ($filters['search'] ?? null) {
            $search = strtolower($filters['search']);
            $classes = $classes->filter(function ($class) use ($search) {
                return str_contains(strtolower($class->name ?? ''), $search) ||
                    str_contains(strtolower($class->display_name ?? ''), $search) ||
                    str_contains(strtolower($class->level->name ?? ''), $search);
            })->values();
        }

        if ($filters['level_id'] ?? null) {
            $classes = $classes->where('level_id', $filters['level_id'])->values();
        }

        $total = $classes->count();
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = $classes->slice($offset, $perPage)->values();

        $paginatedClasses = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Teacher/Classes/Index', [
            'classes' => $paginatedClasses->withQueryString(),
            'classSubjects' => $classSubjects,
            'filters' => $filters,
        ]);
    }

    /**
     * Display details for a specific class including all subjects taught.
     */
    public function show(Request $request, ClassModel $class): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $perPageSubjects = (int) $request->input('subjects_per_page', 10);
        $perPageAssessments = (int) $request->input('assessments_per_page', 10);
        $subjectsSearch = $request->input('subjects_search');
        $assessmentsSearch = $request->input('assessments_search');

        $this->validateAcademicYearAccess($class, $selectedYearId);

        $classSubjectsQuery = ClassSubject::where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->with(['subject', 'assessments'])
            ->when(
                $subjectsSearch,
                fn ($query, $search) => $query->whereHas('subject', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            );

        $classSubjects = $classSubjectsQuery->paginate($perPageSubjects, ['*'], 'subjects_page')
            ->withQueryString();

        $classSubjectIds = ClassSubject::where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->pluck('id');

        $assessmentsQuery = \App\Models\Assessment::query()
            ->whereIn('class_subject_id', $classSubjectIds)
            ->with(['classSubject.subject'])
            ->when(
                $assessmentsSearch,
                fn ($query, $search) => $query->where('title', 'like', "%{$search}%")
            )
            ->latest('scheduled_at');

        $assessments = $assessmentsQuery->paginate($perPageAssessments, ['*'], 'assessments_page')
            ->withQueryString();

        $class->load([
            'academicYear',
            'level',
            'enrollments' => fn ($q) => $q->where('status', 'active'),
        ]);

        return Inertia::render('Teacher/Classes/Show', [
            'class' => $class,
            'subjects' => $classSubjects,
            'assessments' => $assessments,
            'filters' => [
                'subjects_search' => $subjectsSearch,
                'assessments_search' => $assessmentsSearch,
            ],
        ]);
    }
}
