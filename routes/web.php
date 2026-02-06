<?php

use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
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
     * Academic Year Routes
     * API endpoint for academic year selection
     */
    Route::post('/academic-years/set-current', [
        \App\Http\Controllers\AcademicYearController::class,
        'setCurrent',
    ])->name('api.academic-years.set-current');

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

            Route::post('/{id}/restore', 'restore')
                ->name('restore');

            Route::delete('/{id}/force', 'forceDelete')
                ->name('force-delete');
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
     * Admin Routes
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
                    Route::get('/archives', 'archives')->name('archives');
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
     * Teacher Routes
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
     * Student Routes
     * Role-based routes exclusively for students
     */
    Route::middleware('role:student')
        ->prefix('student')
        ->name('student.')
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
