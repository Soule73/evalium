<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Exam\ResultsController;
use App\Http\Controllers\Exam\CorrectionController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Exam\GroupAssignmentController;
use App\Http\Controllers\Admin\LevelManagementController;
use App\Http\Controllers\Student\ExamController as StudentExamController;

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
        ->controller(StudentExamController::class)
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
                    ->middleware('permission:view exams')
                    ->name('index');

                Route::get('/create', 'create')
                    ->middleware('permission:create exams')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:create exams')
                    ->name('store');

                Route::post('/{exam}/duplicate', 'duplicate')
                    ->middleware('permission:create exams')
                    ->name('duplicate');

                Route::patch('/{exam}/toggle-active', 'toggleActive')
                    ->middleware('permission:publish exams')
                    ->name('toggle-active');

                Route::get('/{exam}/edit', 'edit')
                    ->middleware('permission:update exams')
                    ->name('edit');

                Route::put('/{exam}', 'update')
                    ->middleware('permission:update exams')
                    ->name('update');

                Route::delete('/{exam}', 'destroy')
                    ->middleware('permission:delete exams')
                    ->name('destroy');

                Route::get('/{exam}', 'show')
                    ->middleware('permission:view exams')
                    ->name('show');
            });

            /**
             * Group Assignment operations
             * Unique exam assignment mode
             */
            Route::controller(GroupAssignmentController::class)->group(function () {
                Route::get('/{exam}/assign', 'showAssignForm')
                    ->middleware('permission:assign exams')
                    ->name('assign');

                Route::post('/{exam}/assign-groups', 'assignToGroups')
                    ->middleware('permission:assign group exams')
                    ->name('assign.groups');

                Route::get('/{exam}/groups', 'showAssignments')
                    ->middleware('permission:view assignments')
                    ->name('groups');

                Route::delete('/{exam}/groups/{group}', 'removeFromGroup')
                    ->middleware('permission:assign group exams')
                    ->name('groups.remove');

                Route::get('/{exam}/groups/{group}', 'showGroupShow')
                    ->middleware('permission:view assignments')
                    ->name('group.show');
            });

            /**
             * Exam Correction operations
             */
            Route::controller(CorrectionController::class)->group(function () {
                Route::get('/{exam}/groups/{group}/review/{student}', 'showStudentReview')
                    ->middleware('permission:correct exams')
                    ->name('review');

                Route::post('/{exam}/groups/{group}/review/{student}', 'saveStudentReview')
                    ->middleware('permission:grade answers')
                    ->name('review.save');

                Route::post('/{exam}/score/update', 'updateScore')
                    ->middleware('permission:grade assignments')
                    ->name('score.update');
            });

            /**
             * Exam Results operations
             */
            Route::controller(ResultsController::class)->group(function () {
                Route::get('/{exam}/groups/{group}/submissions/{student}', 'showStudentSubmission')
                    ->middleware('permission:view exam results')
                    ->name('submissions');

                Route::get('/{exam}/stats', 'stats')
                    ->middleware('permission:view reports')
                    ->name('stats');
            });
        });

    /**
     * User Management operations
     */
    Route::prefix('users')
        ->name('users.')
        ->controller(UserManagementController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:view users')
                ->name('index');

            Route::get('/students/{user}', 'showStudent')
                ->middleware('permission:view users')
                ->name('show.student');

            Route::get('/teachers/{user}', 'showTeacher')
                ->middleware('permission:view users')
                ->name('show.teacher');

            Route::post('/', 'store')
                ->middleware('permission:create users')
                ->name('store');

            Route::put('/{user}', 'update')
                ->middleware('permission:update users')
                ->name('update');

            Route::delete('/{user}', 'destroy')
                ->middleware('permission:delete users')
                ->name('destroy');

            Route::patch('/{user}/toggle-status', 'toggleStatus')
                ->middleware('permission:toggle user status')
                ->name('toggle-status');

            Route::put('/{user}/change-group', 'changeStudentGroup')
                ->middleware('permission:manage students')
                ->name('change-group');

            Route::post('/{id}/restore', 'restore')
                ->middleware('permission:restore users')
                ->name('restore');

            Route::delete('/{id}/force', 'forceDelete')
                ->middleware('permission:force delete users')
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
                ->middleware('permission:view groups')
                ->name('index');

            Route::get('/create', 'create')
                ->middleware('permission:create groups')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('permission:create groups')
                ->name('store');

            Route::post('/bulk-activate', 'bulkActivate')
                ->middleware('permission:toggle group status')
                ->name('bulk-activate');

            Route::post('/bulk-deactivate', 'bulkDeactivate')
                ->middleware('permission:toggle group status')
                ->name('bulk-deactivate');

            Route::get('/{group}', 'show')
                ->middleware('permission:view groups')
                ->name('show');

            Route::get('/{group}/edit', 'edit')
                ->middleware('permission:update groups')
                ->name('edit');

            Route::put('/{group}', 'update')
                ->middleware('permission:update groups')
                ->name('update');

            Route::delete('/{group}', 'destroy')
                ->middleware('permission:delete groups')
                ->name('destroy');

            Route::get('/{group}/assign-students', 'assignStudents')
                ->middleware('permission:manage group students')
                ->name('assign-students');

            Route::post('/{group}/assign-students', 'storeStudents')
                ->middleware('permission:manage group students')
                ->name('store-students');

            Route::post('/{group}/bulk-remove-students', 'bulkRemoveStudents')
                ->middleware('permission:manage group students')
                ->name('bulk-remove-students');

            Route::delete('/{group}/students/{student}', 'removeStudent')
                ->middleware('permission:manage group students')
                ->name('remove-student');
        });

    /**
     * Level Management operations
     */
    Route::prefix('levels')
        ->name('levels.')
        ->controller(LevelManagementController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:view levels')
                ->name('index');

            Route::get('/create', 'create')
                ->middleware('permission:create levels')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('permission:create levels')
                ->name('store');

            Route::get('/{level}/edit', 'edit')
                ->middleware('permission:update levels')
                ->name('edit');

            Route::put('/{level}', 'update')
                ->middleware('permission:update levels')
                ->name('update');

            Route::delete('/{level}', 'destroy')
                ->middleware('permission:delete levels')
                ->name('destroy');

            Route::patch('/{level}/toggle-status', 'toggleStatus')
                ->middleware('permission:update levels')
                ->name('toggle-status');
        });

    /**
     * Role and Permission Management operations
     */
    Route::prefix('roles')
        ->name('roles.')
        ->controller(RolePermissionController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:view roles')
                ->name('index');

            Route::get('/create', 'create')
                ->middleware('permission:create roles')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('permission:create roles')
                ->name('store');

            Route::get('/permissions', 'permissionsIndex')
                ->middleware('permission:view permissions')
                ->name('permissions.index');

            Route::post('/permissions', 'storePermission')
                ->middleware('permission:create permissions')
                ->name('permissions.store');

            Route::delete('/permissions/{permission}', 'destroyPermission')
                ->middleware('permission:delete permissions')
                ->name('permissions.destroy');

            Route::get('/{role}/edit', 'edit')
                ->middleware('permission:update roles')
                ->name('edit');

            Route::put('/{role}', 'update')
                ->middleware('permission:update roles')
                ->name('update');

            Route::delete('/{role}', 'destroy')
                ->middleware('permission:delete roles')
                ->name('destroy');

            Route::post('/{role}/sync-permissions', 'syncPermissions')
                ->middleware('permission:assign permissions')
                ->name('sync-permissions');
        });
});
