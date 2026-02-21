<?php

namespace App\Http\Controllers\Teacher;

use App\Contracts\Repositories\TeacherSubjectRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherSubjectController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly TeacherSubjectRepositoryInterface $subjectQueryService
    ) {}

    /**
     * Display all subjects where the teacher is assigned.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['search', 'class_id']);

        $subjects = $this->subjectQueryService->getSubjectsForTeacher(
            $teacherId,
            $selectedYearId,
            $filters,
            $perPage
        );

        $classes = $this->subjectQueryService->getClassesForFilter($teacherId, $selectedYearId);

        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'classes' => $classes,
            'filters' => $filters,
            'routeContext' => [
                'role' => 'teacher',
                'indexRoute' => 'teacher.subjects.index',
                'showRoute' => 'teacher.subjects.show',
                'editRoute' => null,
                'deleteRoute' => null,
                'assessmentShowRoute' => 'teacher.assessments.show',
            ],
        ]);
    }

    /**
     * Display details for a specific subject including assessments.
     */
    public function show(Request $request, Subject $subject): Response
    {
        $teacherId = $request->user()->id;
        $selectedYearId = $this->getSelectedAcademicYearId($request);
        $perPage = (int) $request->input('per_page', 10);

        $this->subjectQueryService->authorizeTeacherSubject($teacherId, $subject->id, $selectedYearId);

        $subjectWithDetails = $this->subjectQueryService->getSubjectDetails(
            $subject,
            $teacherId,
            $selectedYearId
        );

        $assessments = $this->subjectQueryService->getAssessmentsForSubject(
            $subject,
            $teacherId,
            $selectedYearId,
            $request->only(['search']),
            $perPage
        );

        return Inertia::render('Subjects/Show', [
            'subject' => $subjectWithDetails,
            'assessments' => $assessments,
            'filters' => $request->only(['search']),
            'routeContext' => [
                'role' => 'teacher',
                'indexRoute' => 'teacher.subjects.index',
                'showRoute' => 'teacher.subjects.show',
                'editRoute' => null,
                'deleteRoute' => null,
                'assessmentShowRoute' => 'teacher.assessments.show',
            ],
        ]);
    }
}
