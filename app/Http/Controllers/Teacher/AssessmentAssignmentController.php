<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasFlashMessages;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentAssignmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    /**
     * Display all assignments for a specific assessment.
     */
    public function index(Assessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        $assessment->load([
            'classSubject.class.students',
            'assignments.student',
            'assignments.answers',
        ]);

        return Inertia::render('Teacher/AssessmentAssignments/Index', [
            'assessment' => $assessment,
        ]);
    }

    /**
     * Assign all students from the class to the assessment.
     */
    public function assignAll(Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $class = $assessment->classSubject->class;
        $students = $class->students;

        foreach ($students as $student) {
            AssessmentAssignment::firstOrCreate([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
            ], [
                'assigned_at' => now(),
            ]);
        }

        return back()->flashSuccess(__('messages.all_students_assigned'));
    }

    /**
     * Assign specific students to the assessment.
     */
    public function assign(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        foreach ($request->input('student_ids') as $studentId) {
            AssessmentAssignment::firstOrCreate([
                'assessment_id' => $assessment->id,
                'student_id' => $studentId,
            ], [
                'assigned_at' => now(),
            ]);
        }

        return back()->flashSuccess(__('messages.students_assigned'));
    }

    /**
     * Unassign a student from an assessment.
     */
    public function unassign(Assessment $assessment, User $student): RedirectResponse
    {
        $this->authorize('update', $assessment);

        AssessmentAssignment::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->delete();

        return back()->flashSuccess(__('messages.student_unassigned'));
    }
}
