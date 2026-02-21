<?php

namespace App\Providers;

use App\Contracts\Repositories\AdminAssessmentRepositoryInterface;
use App\Contracts\Repositories\ClassRepositoryInterface;
use App\Contracts\Repositories\ClassSubjectRepositoryInterface;
use App\Contracts\Repositories\EnrollmentRepositoryInterface;
use App\Contracts\Repositories\LevelRepositoryInterface;
use App\Contracts\Repositories\SubjectRepositoryInterface;
use App\Contracts\Repositories\TeacherAssessmentRepositoryInterface;
use App\Contracts\Repositories\TeacherClassRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\ClassServiceInterface;
use App\Contracts\Services\ClassSubjectServiceInterface;
use App\Contracts\Services\EnrollmentServiceInterface;
use App\Contracts\Services\LevelServiceInterface;
use App\Contracts\Services\SubjectServiceInterface;
use App\Contracts\Services\UserManagementServiceInterface;
use App\Repositories\Admin\AdminAssessmentRepository;
use App\Repositories\Admin\ClassRepository;
use App\Repositories\Admin\ClassSubjectRepository;
use App\Repositories\Admin\EnrollmentRepository;
use App\Repositories\Admin\LevelRepository;
use App\Repositories\Admin\SubjectRepository;
use App\Repositories\Admin\UserRepository;
use App\Repositories\Teacher\TeacherAssessmentRepository;
use App\Repositories\Teacher\TeacherClassRepository;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\ClassService;
use App\Services\Admin\EnrollmentService;
use App\Services\Admin\LevelService;
use App\Services\Admin\SubjectService;
use App\Services\Admin\UserManagementService;
use App\Services\Core\ClassSubjectService;
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
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScoringService::class);
        $this->app->singleton(\App\Services\Core\QuestionCrudService::class);
        $this->app->singleton(\App\Services\Core\ChoiceManagementService::class);
        $this->app->singleton(\App\Services\Core\QuestionDuplicationService::class);
        $this->app->bind(AdminDashboardService::class);

        $this->app->bind(ClassRepositoryInterface::class, ClassRepository::class);
        $this->app->bind(EnrollmentRepositoryInterface::class, EnrollmentRepository::class);
        $this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
        $this->app->bind(LevelRepositoryInterface::class, LevelRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClassSubjectRepositoryInterface::class, ClassSubjectRepository::class);
        $this->app->bind(AdminAssessmentRepositoryInterface::class, AdminAssessmentRepository::class);
        $this->app->bind(TeacherAssessmentRepositoryInterface::class, TeacherAssessmentRepository::class);
        $this->app->bind(TeacherClassRepositoryInterface::class, TeacherClassRepository::class);

        $this->app->bind(ClassServiceInterface::class, ClassService::class);
        $this->app->bind(EnrollmentServiceInterface::class, EnrollmentService::class);
        $this->app->bind(SubjectServiceInterface::class, SubjectService::class);
        $this->app->bind(LevelServiceInterface::class, LevelService::class);
        $this->app->bind(UserManagementServiceInterface::class, UserManagementService::class);
        $this->app->bind(ClassSubjectServiceInterface::class, ClassSubjectService::class);
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
                    'canAssignAssessments' => $user->can('update assessments'),
                    'canCorrectAssessments' => $user->can('grade assessments'),
                    'canViewReports' => $user->can('grade assessments'),
                    'canPublishAssessments' => $user->can('update assessments'),
                    'canGradeAnswers' => $user->can('grade assessments'),

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

                    'canCreateRoles' => false,
                    'canUpdateRoles' => $user->can('update roles'),
                    'canDeleteRoles' => false,
                ];
            },
        ]);
    }
}
