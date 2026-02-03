<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exam\AssignmentController;
use App\Http\Controllers\Exam\CorrectionController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\ResultsController;
use App\Http\Controllers\Group\GroupController;
use App\Http\Controllers\Group\LevelController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

    /**
     * ========================================
     * NEW MCD ARCHITECTURE ROUTES
     * ========================================
     */

    /**
     * Admin Routes - New MCD Architecture
     * Routes for managing academic years, subjects, classes, enrollments
     */
    Route::prefix('admin')
        ->name('admin.')
        ->group(function () {

            /**
             * Academic Year Management
             */
            Route::prefix('academic-years')
                ->name('academic-years.')
                ->controller(\App\Http\Controllers\Admin\AcademicYearController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{academic_year}', 'show')->name('show');
                    Route::get('/{academic_year}/edit', 'edit')->name('edit');
                    Route::put('/{academic_year}', 'update')->name('update');
                    Route::delete('/{academic_year}', 'destroy')->name('destroy');
                    Route::post('/{academic_year}/set-current', 'setCurrent')->name('set-current');
                    Route::post('/{academic_year}/archive', 'archive')->name('archive');
                });

            /**
             * Subject Management
             */
            Route::prefix('subjects')
                ->name('subjects.')
                ->controller(\App\Http\Controllers\Admin\SubjectController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{subject}', 'show')->name('show');
                    Route::get('/{subject}/edit', 'edit')->name('edit');
                    Route::put('/{subject}', 'update')->name('update');
                    Route::delete('/{subject}', 'destroy')->name('destroy');
                });

            /**
             * Class Management
             */
            Route::prefix('classes')
                ->name('classes.')
                ->controller(\App\Http\Controllers\Admin\ClassController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{class}', 'show')->name('show');
                    Route::get('/{class}/edit', 'edit')->name('edit');
                    Route::put('/{class}', 'update')->name('update');
                    Route::delete('/{class}', 'destroy')->name('destroy');
                });

            /**
             * Enrollment Management
             */
            Route::prefix('enrollments')
                ->name('enrollments.')
                ->controller(\App\Http\Controllers\Admin\EnrollmentController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{enrollment}', 'show')->name('show');
                    Route::post('/{enrollment}/transfer', 'transfer')->name('transfer');
                    Route::post('/{enrollment}/withdraw', 'withdraw')->name('withdraw');
                    Route::post('/{enrollment}/reactivate', 'reactivate')->name('reactivate');
                    Route::delete('/{enrollment}', 'destroy')->name('destroy');
                });

            /**
             * Class Subject Management (Central table for teacher-subject-class assignments)
             */
            Route::prefix('class-subjects')
                ->name('class-subjects.')
                ->controller(\App\Http\Controllers\Admin\ClassSubjectController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{class_subject}', 'show')->name('show');
                    Route::get('/history', 'history')->name('history');
                    Route::post('/{class_subject}/replace-teacher', 'replaceTeacher')->name('replace-teacher');
                    Route::post('/{class_subject}/update-coefficient', 'updateCoefficient')->name('update-coefficient');
                    Route::post('/{class_subject}/terminate', 'terminate')->name('terminate');
                    Route::delete('/{class_subject}', 'destroy')->name('destroy');
                });
        });

    /**
     * Teacher Routes - New MCD Architecture
     * Routes for managing assessments, grading, and teacher dashboard
     */
    Route::prefix('teacher')
        ->name('teacher.')
        ->group(function () {

            /**
             * Teacher Dashboard
             */
            Route::get('/dashboard', [\App\Http\Controllers\Teacher\TeacherDashboardController::class, 'index'])
                ->name('dashboard');

            /**
             * Assessment Management
             */
            Route::prefix('assessments')
                ->name('assessments.')
                ->controller(\App\Http\Controllers\Teacher\AssessmentController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('/{assessment}', 'show')->name('show');
                    Route::get('/{assessment}/edit', 'edit')->name('edit');
                    Route::put('/{assessment}', 'update')->name('update');
                    Route::delete('/{assessment}', 'destroy')->name('destroy');
                    Route::post('/{assessment}/publish', 'publish')->name('publish');
                    Route::post('/{assessment}/unpublish', 'unpublish')->name('unpublish');
                    Route::post('/{assessment}/duplicate', 'duplicate')->name('duplicate');
                });

            /**
             * Assessment Assignment Management
             */
            Route::prefix('assessments/{assessment}/assignments')
                ->name('assessment-assignments.')
                ->controller(\App\Http\Controllers\Teacher\AssessmentAssignmentController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/assign-all', 'assignAll')->name('assign-all');
                    Route::post('/assign', 'assign')->name('assign');
                    Route::delete('/{student}', 'unassign')->name('unassign');
                });

            /**
             * Teacher Classes
             */
            Route::prefix('classes')
                ->name('classes.')
                ->controller(\App\Http\Controllers\Teacher\TeacherClassController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{class}', 'show')->name('show');
                });

            /**
             * Grading
             */
            Route::prefix('grading')
                ->name('grading.')
                ->controller(\App\Http\Controllers\Teacher\GradingController::class)
                ->group(function () {
                    Route::get('/assessments/{assessment}', 'index')->name('index');
                    Route::get('/assessments/{assessment}/students/{student}', 'show')->name('show');
                    Route::post('/assessments/{assessment}/students/{student}', 'save')->name('save');
                    Route::get('/students/{student}/classes/{class}/breakdown', 'breakdown')->name('breakdown');
                });
        });

    /**
     * Student Routes - New MCD Architecture
     * Role-based routes exclusively for students
     */
    Route::middleware('role:student')
        ->prefix('student/mcd')
        ->name('student.mcd.')
        ->group(function () {

            /**
             * Student Assessments
             */
            Route::prefix('assessments')
                ->name('assessments.')
                ->controller(\App\Http\Controllers\Student\StudentAssessmentController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{assessment}', 'show')->name('show');
                    Route::post('/{assessment}/start', 'start')->name('start');
                    Route::get('/{assessment}/take', 'take')->name('take');
                    Route::post('/{assessment}/save-answers', 'saveAnswers')->name('save-answers');
                    Route::post('/{assessment}/submit', 'submit')->name('submit');
                    Route::get('/{assessment}/results', 'results')->name('results');
                });

            /**
             * Student Enrollment
             */
            Route::prefix('enrollment')
                ->name('enrollment.')
                ->controller(\App\Http\Controllers\Student\StudentEnrollmentController::class)
                ->group(function () {
                    Route::get('/', 'show')->name('show');
                    Route::get('/history', 'history')->name('history');
                    Route::get('/classmates', 'classmates')->name('classmates');
                });
        });
});
