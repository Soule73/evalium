<?php

namespace App\Providers;

use App\Services\ExamService;
use Illuminate\Support\ServiceProvider;
use App\Services\Shared\DashboardService;
use App\Services\Shared\UserAnswerService;
use App\Services\Student\ExamSessionService;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\UserManagementService;
use App\Services\Teacher\ExamAssignmentService;
use App\Services\Teacher\TeacherDashboardService;
use App\Services\Teacher\ExamScoringService as TeacherExamScoringService;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Core\Answer\AnswerFormatter;
use App\Services\Core\Answer\AnswerFormatterInterface;

class AppServiceProvider extends ServiceProvider
{
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
        //
    }
}
