<?php

namespace App\Providers;

use App\Services\ExamService;
use Illuminate\Support\ServiceProvider;
use App\Services\Shared\DashboardService;
use App\Services\Shared\UserAnswerService;
use App\Services\Student\ExamSessionService;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\UserManagementService;
use App\Services\Exam\ExamAssignmentService;
use App\Services\Exam\TeacherDashboardService;
use App\Services\Exam\ExamScoringService as TeacherExamScoringService;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Core\Answer\AnswerFormatter;
use App\Services\Core\Answer\AnswerFormatterInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Exam::class => \App\Policies\ExamPolicy::class,
        \App\Models\Group::class => \App\Policies\GroupPolicy::class,
        \App\Models\Level::class => \App\Policies\LevelPolicy::class,
        \App\Models\Question::class => \App\Policies\QuestionPolicy::class,
        \App\Models\Answer::class => \App\Policies\AnswerPolicy::class,
        \App\Models\ExamAssignment::class => \App\Policies\ExamAssignmentPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Core services (singleton pour réutilisation)
        $this->app->singleton(ScoringService::class, ScoringService::class);
        $this->app->singleton(AnswerFormatterInterface::class, AnswerFormatter::class);
        $this->app->singleton(AnswerFormatter::class, AnswerFormatter::class);

        $this->app->bind(ExamService::class, ExamService::class);

        $this->app->bind(ExamSessionService::class, ExamSessionService::class);

        $this->app->bind(ExamAssignmentService::class, ExamAssignmentService::class);
        $this->app->bind(TeacherExamScoringService::class, TeacherExamScoringService::class);

        $this->app->bind(UserAnswerService::class, UserAnswerService::class);
        $this->app->bind(DashboardService::class, DashboardService::class);

        $this->app->bind(TeacherDashboardService::class, TeacherDashboardService::class);
        $this->app->bind(AdminDashboardService::class, AdminDashboardService::class);

        $this->app->bind(UserManagementService::class, UserManagementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer les policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Partager les permissions spécifiques avec Inertia pour la navigation
        \Inertia\Inertia::share([
            'permissions' => function () {
                $user = Auth::user();
                if (!$user) {
                    return [
                        'canManageLevels' => false,
                        'canManageRoles' => false,
                        'canManageUsers' => false,
                        'canManageGroups' => false,
                        'canManageExams' => false,
                        'canViewReports' => false,
                        'canExportReports' => false,
                    ];
                }

                return [
                    // Navigation permissions
                    'canManageLevels' => $user->can('view levels'),
                    'canManageRoles' => $user->can('view roles'),
                    'canManageUsers' => $user->can('view users'),
                    'canManageGroups' => $user->can('view groups'),
                    'canManageExams' => $user->can('view exams'),

                    // Feature permissions
                    'canViewReports' => $user->can('view reports'),
                    'canExportReports' => $user->can('export reports'),
                    'canCreateExams' => $user->can('create exams'),
                    'canPublishExams' => $user->can('publish exams'),
                    'canAssignExams' => $user->can('assign exams'),
                    'canCorrectExams' => $user->can('correct exams'),
                    'canGradeAnswers' => $user->can('grade answers'),

                    // User management
                    'canCreateUsers' => $user->can('create users'),
                    'canUpdateUsers' => $user->can('update users'),
                    'canDeleteUsers' => $user->can('delete users'),
                    'canManageStudents' => $user->can('manage students'),
                    'canManageTeachers' => $user->can('manage teachers'),

                    // Group management
                    'canCreateGroups' => $user->can('create groups'),
                    'canUpdateGroups' => $user->can('update groups'),
                    'canDeleteGroups' => $user->can('delete groups'),
                    'canManageGroupStudents' => $user->can('manage group students'),

                    // Level management
                    'canCreateLevels' => $user->can('create levels'),
                    'canUpdateLevels' => $user->can('update levels'),
                    'canDeleteLevels' => $user->can('delete levels'),

                    // Role management
                    'canCreateRoles' => $user->can('create roles'),
                    'canUpdateRoles' => $user->can('update roles'),
                    'canDeleteRoles' => $user->can('delete roles'),
                    'canAssignPermissions' => $user->can('assign permissions'),
                ];
            }
        ]);
    }
}
