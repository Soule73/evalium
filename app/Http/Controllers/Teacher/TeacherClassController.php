<?php

namespace App\Http\Controllers\Teacher;

use App\Contracts\Repositories\TeacherClassRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Traits\FiltersAcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherClassController extends Controller
{
    use FiltersAcademicYear;

    public function __construct(
        private readonly TeacherClassRepositoryInterface $classQueryService
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

        $paginatedClasses = $this->classQueryService->getClassesForTeacher(
            $teacherId,
            $selectedYearId,
            $filters,
            $perPage
        );

        return Inertia::render('Teacher/Classes/Index', [
            'classes' => $paginatedClasses->withQueryString(),
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
        $perPageStudents = (int) $request->input('students_per_page', 10);

        $this->classQueryService->validateAcademicYearAccess($class, $selectedYearId);

        $filters = [
            'subjects_search' => $request->input('subjects_search'),
            'assessments_search' => $request->input('assessments_search'),
            'students_search' => $request->input('students_search'),
        ];

        $classSubjects = $this->classQueryService->getSubjectsForClass(
            $class,
            $teacherId,
            $filters,
            $perPageSubjects
        );

        $assessments = $this->classQueryService->getAssessmentsForClass(
            $class,
            $teacherId,
            $filters,
            $perPageAssessments
        );

        $students = $this->classQueryService->getStudentsForClass(
            $class,
            $filters,
            $perPageStudents
        );

        $class->load([
            'academicYear',
            'level',
        ]);

        return Inertia::render('Teacher/Classes/Show', [
            'class' => $class,
            'subjects' => $classSubjects,
            'assessments' => $assessments,
            'students' => $students,
            'filters' => $filters,
        ]);
    }
}
