<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    /**
     * Display the teacher dashboard with overview statistics.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;

        $activeAssignments = ClassSubject::where('teacher_id', $teacherId)
            ->active()
            ->with(['class', 'subject'])
            ->get();

        $recentAssessments = Assessment::whereHas('classSubject', fn($query) => $query->where('teacher_id', $teacherId))
            ->with(['classSubject.class', 'classSubject.subject'])
            ->orderBy('scheduled_date', 'desc')
            ->limit(5)
            ->get();

        $upcomingAssessments = Assessment::whereHas('classSubject', fn($query) => $query->where('teacher_id', $teacherId))
            ->where('scheduled_date', '>=', now())
            ->where('is_published', true)
            ->with(['classSubject.class', 'classSubject.subject'])
            ->orderBy('scheduled_date', 'asc')
            ->limit(5)
            ->get();

        $stats = [
            'total_classes' => $activeAssignments->unique('class_id')->count(),
            'total_subjects' => $activeAssignments->unique('subject_id')->count(),
            'total_assessments' => Assessment::whereHas('classSubject', fn($query) => $query->where('teacher_id', $teacherId))->count(),
            'published_assessments' => Assessment::whereHas('classSubject', fn($query) => $query->where('teacher_id', $teacherId))
                ->where('is_published', true)
                ->count(),
        ];

        return Inertia::render('Teacher/Dashboard', [
            'activeAssignments' => $activeAssignments,
            'recentAssessments' => $recentAssessments,
            'upcomingAssessments' => $upcomingAssessments,
            'stats' => $stats,
        ]);
    }
}
