<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Teacher\ExamController as TeacherExamController;
use App\Http\Controllers\Teacher\ExamAssignmentController;
use App\Http\Controllers\Teacher\ExamGroupAssignmentController;
use App\Http\Controllers\Teacher\ExamCorrectionController;
use App\Http\Controllers\Teacher\ExamResultsController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\GroupController;
use Inertia\Inertia;

// Routes publiques
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// Routes d'authentification
Route::middleware('guest')
    ->controller(LoginController::class)
    ->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login')->name('login.attempt');
    });

// Routes protégées nécessitant une authentification
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(LoginController::class)->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile/{user}', 'editProfile')->name('profile.update');
        Route::post('/logout', 'logout')->name('logout');
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/dashboard/student', [DashboardController::class, 'student'])->name('student.dashboard');

        Route::controller(StudentExamController::class)
            ->prefix('student/exams')
            ->name('student.exams.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{exam}', 'show')->name('show');
                Route::get('/{exam}/take', 'take')->name('take');
                Route::post('/{exam}/save-answers', 'saveAnswers')->name('save-answers');
                Route::post('/{exam}/security-violation', 'handleSecurityViolation')->name('security-violation');
                Route::post('/{exam}/abandon', 'abandon')->name('abandon');
                Route::post('/{exam}/submit', 'submit')->name('submit');
            });
    });

    Route::middleware('role:teacher')->group(function () {
        Route::get('/dashboard/teacher', [DashboardController::class, 'teacher'])->name('teacher.dashboard');

        // Routes CRUD pour les examens
        Route::controller(TeacherExamController::class)
            ->prefix('teacher/exams')
            ->name('teacher.exams.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{exam}', 'show')->name('show');
                Route::get('/{exam}/edit', 'edit')->name('edit');
                Route::put('/{exam}', 'update')->name('update');
                Route::delete('/{exam}', 'destroy')->name('destroy');
                Route::post('/{exam}/duplicate', 'duplicate')->name('duplicate');
                Route::patch('/{exam}/toggle-active', 'toggleActive')->name('toggle-active');
            });

        // Routes pour les assignations d'étudiants
        Route::controller(ExamAssignmentController::class)
            ->prefix('teacher/exams')
            ->name('teacher.exams.')
            ->group(function () {
                Route::get('/{exam}/assign', 'showAssignForm')->name('assign');
                Route::get('/{exam}/assign/show', 'showAssignForm')->name('assign.show');
                Route::post('/{exam}/assign', 'assignToStudents')->name('assign.store');
                Route::get('/{exam}/assignments', 'showAssignments')->name('assignments');
                Route::delete('/{exam}/assignments/{user}', 'removeAssignment')->name('assignment.remove');
            });

        // Routes pour les assignations de groupes
        Route::controller(ExamGroupAssignmentController::class)
            ->prefix('teacher/exams')
            ->name('teacher.exams.')
            ->group(function () {
                Route::post('/{exam}/assign-groups', 'assignToGroups')->name('assign.groups');
                Route::delete('/{exam}/groups/{group}', 'removeFromGroup')->name('groups.remove');
            });

        // Routes pour les corrections
        Route::controller(ExamCorrectionController::class)
            ->prefix('teacher/exams')
            ->name('teacher.exams.')
            ->group(function () {
                Route::get('/{exam}/review/{student}', 'showStudentReview')->name('review');
                Route::post('/{exam}/review/{student}', 'saveStudentReview')->name('review.save');
                Route::post('/{exam}/score/update', 'updateScore')->name('score.update');
            });

        // Routes pour les résultats et statistiques
        Route::controller(ExamResultsController::class)
            ->prefix('teacher/exams')
            ->name('teacher.exams.')
            ->group(function () {
                Route::get('/{exam}/results/{student}', 'showStudentResults')->name('results');
                Route::get('/{exam}/results/{student}/show', 'showStudentResults')->name('results.show');
                Route::get('/{exam}/stats', 'stats')->name('stats');
            });
    });

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard');

            Route::prefix('/users')->name('users.')
                ->controller(UserManagementController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    // Route::get('/{user}', 'show')->name('show');
                    Route::get('/students/{user}', 'showStudent')->name('show.student');
                    Route::get('/teachers/{user}', 'showTeacher')->name('show.teacher');
                    Route::post('/', 'store')->name('store');
                    Route::put('/{user}', 'update')->name('update');
                    Route::delete('/{user}', 'destroy')->name('destroy');
                    Route::put('/{user}/change-group', 'changeStudentGroup')->name('change-group');
                });

            Route::prefix('/groups')->name('groups.')
                ->controller(GroupController::class)
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::post('/bulk-activate', 'bulkActivate')->name('bulk-activate');
                    Route::post('/bulk-deactivate', 'bulkDeactivate')->name('bulk-deactivate');
                    Route::get('/{group}', 'show')->name('show');
                    Route::get('/{group}/edit', 'edit')->name('edit');
                    Route::put('/{group}', 'update')->name('update');
                    Route::delete('/{group}', 'destroy')->name('destroy');
                    Route::get('/{group}/assign-students', 'assignStudents')->name('assign-students');
                    Route::post('/{group}/assign-students', 'storeStudents')->name('store-students');
                    Route::post('/{group}/bulk-remove-students', 'bulkRemoveStudents')->name('bulk-remove-students');
                    Route::delete('/{group}/students/{student}', 'removeStudent')->name('remove-student');
                });
        });
});
