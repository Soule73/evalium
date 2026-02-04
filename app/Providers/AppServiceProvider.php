<?php

namespace App\Providers;

use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\UserManagementService;
use App\Services\Core\Scoring\ScoringService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Level::class => \App\Policies\LevelPolicy::class,
        \App\Models\AcademicYear::class => \App\Policies\AcademicYearPolicy::class,
        \App\Models\Subject::class => \App\Policies\SubjectPolicy::class,
        \App\Models\ClassModel::class => \App\Policies\ClassPolicy::class,
        \App\Models\Enrollment::class => \App\Policies\EnrollmentPolicy::class,
        \App\Models\ClassSubject::class => \App\Policies\ClassSubjectPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScoringService::class);
        $this->app->singleton(\App\Services\Core\QuestionManagementService::class);
        $this->app->bind(AdminDashboardService::class);
        $this->app->bind(UserManagementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        \Inertia\Inertia::share([
            'permissions' => function () {
                $user = Auth::user();
                if (! $user) {
                    return [
                        'canManageLevels' => false,
                        'canManageRoles' => false,
                        'canManageUsers' => false,
                        'canManageClasses' => false,
                        'canManageAssessments' => false,
                    ];
                }

                /** @var \App\Models\User $user */
                return [
                    'canManageLevels' => $user->can('view levels'),
                    'canManageRoles' => $user->can('view roles'),
                    'canManageUsers' => $user->can('view users'),
                    'canManageAcademicYears' => $user->can('view academic years'),
                    'canManageSubjects' => $user->can('view subjects'),
                    'canManageClasses' => $user->can('view classes'),
                    'canManageEnrollments' => $user->can('view enrollments'),
                    'canManageClassSubjects' => $user->can('view class subjects'),
                    'canManageAssessments' => $user->can('view assessments'),

                    'canCreateAssessments' => $user->can('create assessments'),
                    'canAssignAssessments' => $user->can('assign assessments'),
                    'canCorrectAssessments' => $user->can('correct assessments'),
                    'canViewReports' => $user->can('view assessment results'),
                    'canPublishAssessments' => $user->can('update assessments'),
                    'canGradeAnswers' => $user->can('correct assessments'),

                    'canCreateUsers' => $user->can('create users'),
                    'canUpdateUsers' => $user->can('update users'),
                    'canDeleteUsers' => $user->can('delete users'),
                    'canManageStudents' => $user->can('manage students'),
                    'canManageTeachers' => $user->can('view users'),

                    'canCreateClasses' => $user->can('create classes'),
                    'canUpdateClasses' => $user->can('update classes'),
                    'canDeleteClasses' => $user->can('delete classes'),

                    'canCreateLevels' => $user->can('create levels'),
                    'canUpdateLevels' => $user->can('update levels'),
                    'canDeleteLevels' => $user->can('delete levels'),

                    'canCreateRoles' => $user->can('create roles'),
                    'canUpdateRoles' => $user->can('update roles'),
                    'canDeleteRoles' => $user->can('delete roles'),
                ];
            },
        ]);
    }
}
