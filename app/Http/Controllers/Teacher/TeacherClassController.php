<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Services\Core\ClassSubjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherClassController extends Controller
{
    public function __construct(
        private readonly ClassSubjectService $classSubjectService
    ) {}

    /**
     * Display all classes where the teacher is assigned.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;

        $classSubjects = ClassSubject::where('teacher_id', $teacherId)
            ->active()
            ->with([
                'class.academicYear',
                'class.level',
                'class.students',
                'subject',
                'assessments',
            ])
            ->get();

        $classes = $classSubjects->unique('class_id')->pluck('class');

        return Inertia::render('Teacher/Classes/Index', [
            'classes' => $classes,
            'classSubjects' => $classSubjects,
        ]);
    }

    /**
     * Display details for a specific class including all subjects taught.
     */
    public function show(Request $request, ClassModel $class): Response
    {
        $teacherId = $request->user()->id;

        $subjects = $this->classSubjectService->getSubjectsForClass($class, true)
            ->where('teacher_id', $teacherId);

        $class->load([
            'academicYear',
            'level',
            'students',
        ]);

        return Inertia::render('Teacher/Classes/Show', [
            'class' => $class,
            'subjects' => $subjects,
        ]);
    }
}
