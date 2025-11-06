<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Group\GroupController;
use App\Http\Controllers\Exam\ResultsController;
use App\Http\Controllers\Exam\CorrectionController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Exam\AssignmentController;
use App\Http\Controllers\Group\LevelController;
use App\Http\Controllers\StudentController;

/**
 * Public Routes
 * Routes accessible without authentication
 */
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

/**
 * Authentication Routes
 * Login and authentication related routes for guest users
 */
Route::middleware('guest')
    ->controller(LoginController::class)
    ->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login')->name('login.attempt');
    });

/**
 * Authenticated Routes
 * All routes requiring user authentication
 */
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(LoginController::class)->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile/{user}', 'editProfile')->name('profile.update');
        Route::post('/logout', 'logout')->name('logout');
    });

    // Locale Management
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    /**
     * Student Routes
     * Role-based routes exclusively for students (hasRole, not assignable via permissions)
     */
    Route::middleware('role:student')
        ->prefix('student')
        ->name('student.')
        ->controller(StudentController::class)
        ->group(function () {
            Route::prefix('exams')
                ->name('exams.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/groups/{group}', 'showGroup')->name('group.show');
                    Route::get('/{exam}', 'show')->name('show');
                    Route::get('/{exam}/take', 'take')->name('take');
                    Route::post('/{exam}/save-answers', 'saveAnswers')->name('save-answers');
                    Route::post('/{exam}/security-violation', 'handleSecurityViolation')->name('security-violation');
                    Route::post('/{exam}/abandon', 'abandon')->name('abandon');
                    Route::post('/{exam}/submit', 'submit')->name('submit');
                });
        });

    /**
     * Exam Routes
     * Permission-based routes for exam management
     */
    Route::prefix('exams')
        ->name('exams.')
        ->group(function () {

            /**
             * Exam CRUD operations
             */
            Route::controller(ExamController::class)->group(function () {
                Route::get('/', 'index')
                    ->name('index');

                Route::get('/create', 'create')
                    ->name('create');

                Route::post('/', 'store')
                    ->name('store');

                Route::post('/{exam}/duplicate', 'duplicate')
                    ->name('duplicate');

                Route::patch('/{exam}/toggle-active', 'toggleActive')
                    ->name('toggle-active');

                Route::get('/{exam}/edit', 'edit')
                    ->name('edit');

                Route::put('/{exam}', 'update')
                    ->name('update');

                Route::delete('/{exam}', 'destroy')
                    ->name('destroy');

                Route::get('/{exam}', 'show')
                    ->name('show');
            });

            /**
             * Group Assignment operations
             * Unique exam assignment mode
             */
            Route::controller(AssignmentController::class)->group(function () {
                Route::get('/{exam}/assign', 'showAssignForm')
                    ->name('assign');

                Route::post('/{exam}/assign-groups', 'assignToGroups')
                    ->name('assign.groups');

                Route::get('/{exam}/groups', 'showAssignments')
                    ->name('groups');

                Route::delete('/{exam}/groups/{group}', 'removeFromGroup')
                    ->name('groups.remove');

                Route::get('/{exam}/groups/{group}', 'showGroup')
                    ->name('group.show');
            });

            /**
             * Exam Correction operations
             */
            Route::controller(CorrectionController::class)->group(function () {
                Route::get('/{exam}/groups/{group}/review/{student}', 'showStudentReview')
                    ->name('review');

                Route::post('/{exam}/groups/{group}/review/{student}', 'saveStudentReview')
                    ->name('review.save');

                Route::post('/{exam}/score/update', 'updateScore')
                    ->name('score.update');
            });

            /**
             * Exam Results operations
             */
            Route::controller(ResultsController::class)->group(function () {
                Route::get('/{exam}/groups/{group}/submissions/{student}', 'showStudentSubmission')
                    ->name('submissions');

                Route::get('/{exam}/stats', 'stats')
                    ->name('stats');
            });
        });

    /**
     * User Management operations
     */
    Route::prefix('users')
        ->name('users.')
        ->controller(UserController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/students/{user}', 'showStudent')
                ->name('show.student');

            Route::get('/teachers/{user}', 'showTeacher')
                ->name('show.teacher');

            Route::post('/', 'store')
                ->name('store');

            Route::put('/{user}', 'update')
                ->name('update');

            Route::delete('/{user}', 'destroy')
                ->name('destroy');

            Route::patch('/{user}/toggle-status', 'toggleStatus')
                ->name('toggle-status');

            Route::put('/{user}/change-group', 'changeStudentGroup')
                ->name('change-group');

            Route::post('/{id}/restore', 'restore')
                ->name('restore');

            Route::delete('/{id}/force', 'forceDelete')
                ->name('force-delete');
        });

    /**
     * Group Management operations
     */
    Route::prefix('groups')
        ->name('groups.')
        ->controller(GroupController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/create', 'create')
                ->name('create');

            Route::post('/', 'store')
                ->name('store');

            Route::post('/bulk-activate', 'bulkActivate')
                ->name('bulk-activate');

            Route::post('/bulk-deactivate', 'bulkDeactivate')
                ->name('bulk-deactivate');

            Route::get('/{group}', 'show')
                ->name('show');

            Route::get('/{group}/edit', 'edit')
                ->name('edit');

            Route::put('/{group}', 'update')
                ->name('update');

            Route::delete('/{group}', 'destroy')
                ->name('destroy');

            Route::get('/{group}/assign-students', 'assignStudents')
                ->name('assign-students');

            Route::post('/{group}/assign-students', 'storeStudents')
                ->name('store-students');

            Route::post('/{group}/bulk-remove-students', 'bulkRemoveStudents')
                ->name('bulk-remove-students');

            Route::delete('/{group}/students/{student}', 'removeStudent')
                ->name('remove-student');
        });

    /**
     * Level Management operations
     */
    Route::prefix('levels')
        ->name('levels.')
        ->controller(LevelController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/create', 'create')
                ->name('create');

            Route::post('/', 'store')
                ->name('store');

            Route::get('/{level}/edit', 'edit')
                ->name('edit');

            Route::put('/{level}', 'update')
                ->name('update');

            Route::delete('/{level}', 'destroy')
                ->name('destroy');

            Route::patch('/{level}/toggle-status', 'toggleStatus')
                ->name('toggle-status');
        });

    /**
     * Role and Permission Management operations
     */
    Route::prefix('roles')
        ->name('roles.')
        ->controller(RoleController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/create', 'create')
                ->name('create');

            Route::post('/', 'store')
                ->name('store');

            Route::get('/{role}/edit', 'edit')
                ->name('edit');

            Route::put('/{role}', 'update')
                ->name('update');

            Route::delete('/{role}', 'destroy')
                ->name('destroy');

            Route::post('/{role}/sync-permissions', 'syncPermissions')
                ->name('sync-permissions');
        });
});
