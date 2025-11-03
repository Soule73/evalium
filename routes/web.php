<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Exam\ResultsController;
use App\Http\Controllers\Exam\CorrectionController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Exam\GroupAssignmentController;
use App\Http\Controllers\Admin\LevelManagementController;
use App\Http\Controllers\Student\ExamController as StudentExamController;

// ==================== PUBLIC ROUTES ====================

// Page d'accueil
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// ==================== AUTHENTICATION ROUTES ====================

Route::middleware('guest')
    ->controller(LoginController::class)
    ->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login')->name('login.attempt');
    });

// ==================== AUTHENTICATED ROUTES ====================

Route::middleware('auth')->group(function () {

    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil utilisateur
    Route::controller(LoginController::class)->group(function () {
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile/{user}', 'editProfile')->name('profile.update');
        Route::post('/logout', 'logout')->name('logout');
    });

    // ==================== STUDENT ROUTES (Role-Based) ====================
    // Routes strictement réservées au rôle "student" (hasRole, non assignables via permissions)

    Route::middleware('role:student')
        ->prefix('student')
        ->name('student.')
        ->controller(StudentExamController::class)
        ->group(function () {
            Route::prefix('exams')
                ->name('exams.')
                ->group(function () {
                    // Liste des examens
                    Route::get('/', 'index')->name('index');

                    // Détails d'un examen
                    Route::get('/{exam}', 'show')->name('show');

                    // Passer un examen
                    Route::get('/{exam}/take', 'take')->name('take');

                    // Sauvegarder les réponses en cours
                    Route::post('/{exam}/save-answers', 'saveAnswers')->name('save-answers');

                    // Gérer les violations de sécurité
                    Route::post('/{exam}/security-violation', 'handleSecurityViolation')->name('security-violation');

                    // Abandonner un examen
                    Route::post('/{exam}/abandon', 'abandon')->name('abandon');

                    // Soumettre un examen
                    Route::post('/{exam}/submit', 'submit')->name('submit');
                });
        });

    // ==================== EXAM ROUTES (Permission-Based) ====================

    Route::prefix('exams')
        ->name('exams.')
        ->group(function () {

            // ========== EXAM CRUD ==========
            Route::controller(ExamController::class)->group(function () {
                // Liste (index en premier)
                Route::get('/', 'index')
                    ->middleware('permission:view exams')
                    ->name('index');

                // Create (routes spécifiques AVANT les routes paramétrées)
                Route::get('/create', 'create')
                    ->middleware('permission:create exams')
                    ->name('create');

                Route::post('/', 'store')
                    ->middleware('permission:create exams')
                    ->name('store');

                // Actions paramétrées spécifiques (AVANT show)
                Route::post('/{exam}/duplicate', 'duplicate')
                    ->middleware('permission:create exams')
                    ->name('duplicate');

                Route::patch('/{exam}/toggle-active', 'toggleActive')
                    ->middleware('permission:publish exams')
                    ->name('toggle-active');

                // Edit
                Route::get('/{exam}/edit', 'edit')
                    ->middleware('permission:update exams')
                    ->name('edit');

                Route::put('/{exam}', 'update')
                    ->middleware('permission:update exams')
                    ->name('update');

                // Delete
                Route::delete('/{exam}', 'destroy')
                    ->middleware('permission:delete exams')
                    ->name('destroy');

                // Show (EN DERNIER pour éviter les conflits)
                Route::get('/{exam}', 'show')
                    ->middleware('permission:view exams')
                    ->name('show');
            });

            // ========== GROUP ASSIGNMENTS (UNIQUE MODE D'ASSIGNATION) ==========
            Route::controller(GroupAssignmentController::class)->group(function () {
                // Formulaire d'assignation par groupes
                Route::get('/{exam}/assign', 'showAssignForm')
                    ->middleware('permission:assign exams')
                    ->name('assign');

                // Assigner l'examen à des groupes
                Route::post('/{exam}/assign-groups', 'assignToGroups')
                    ->middleware('permission:assign group exams')
                    ->name('assign.groups');

                // Voir les assignations et statistiques
                Route::get('/{exam}/groups', 'showAssignments')
                    ->middleware('permission:view assignments')
                    ->name('groups');

                // Retirer l'examen d'un groupe
                Route::delete('/{exam}/groups/{group}', 'removeFromGroup')
                    ->middleware('permission:assign group exams')
                    ->name('groups.remove');

                // Voir les détails d'un groupe pour un examen
                Route::get('/{exam}/groups/{group}', 'showGroupShow')
                    ->middleware('permission:view assignments')
                    ->name('group.show');
            });

            // ========== EXAM CORRECTION ==========
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

            // ========== EXAM RESULTS ==========
            Route::controller(ResultsController::class)->group(function () {
                Route::get('/{exam}/groups/{group}/submissions/{student}', 'showStudentSubmission')
                    ->middleware('permission:view exam results')
                    ->name('submissions');

                Route::get('/{exam}/stats', 'stats')
                    ->middleware('permission:view reports')
                    ->name('stats');
            });
        });

    // ==================== MANAGEMENT ROUTES (Permission-Based) ====================
    // Routes basées uniquement sur les permissions, accessibles selon les droits de l'utilisateur

    // ========== USER MANAGEMENT ==========
    Route::prefix('users')
        ->name('users.')
        ->controller(UserManagementController::class)
        ->group(function () {
            // Liste
            Route::get('/', 'index')
                ->middleware('permission:view users')
                ->name('index');

            // Affichage
            Route::get('/students/{user}', 'showStudent')
                ->middleware('permission:view users')
                ->name('show.student');

            Route::get('/teachers/{user}', 'showTeacher')
                ->middleware('permission:view users')
                ->name('show.teacher');

            // Création
            Route::post('/', 'store')
                ->middleware('permission:create users')
                ->name('store');

            // Modification
            Route::put('/{user}', 'update')
                ->middleware('permission:update users')
                ->name('update');

            // Suppression
            Route::delete('/{user}', 'destroy')
                ->middleware('permission:delete users')
                ->name('destroy');

            // Actions spécifiques
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

    // ========== GROUP MANAGEMENT ==========
    Route::prefix('groups')
        ->name('groups.')
        ->controller(GroupController::class)
        ->group(function () {
            // Liste
            Route::get('/', 'index')
                ->middleware('permission:view groups')
                ->name('index');

            // Create (AVANT /{group})
            Route::get('/create', 'create')
                ->middleware('permission:create groups')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('permission:create groups')
                ->name('store');

            // Actions en lot (AVANT /{group})
            Route::post('/bulk-activate', 'bulkActivate')
                ->middleware('permission:toggle group status')
                ->name('bulk-activate');

            Route::post('/bulk-deactivate', 'bulkDeactivate')
                ->middleware('permission:toggle group status')
                ->name('bulk-deactivate');

            // Affichage
            Route::get('/{group}', 'show')
                ->middleware('permission:view groups')
                ->name('show');

            // Modification
            Route::get('/{group}/edit', 'edit')
                ->middleware('permission:update groups')
                ->name('edit');

            Route::put('/{group}', 'update')
                ->middleware('permission:update groups')
                ->name('update');

            // Suppression
            Route::delete('/{group}', 'destroy')
                ->middleware('permission:delete groups')
                ->name('destroy');

            // Gestion des étudiants
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

    // ========== LEVEL MANAGEMENT ==========
    Route::prefix('levels')
        ->name('levels.')
        ->controller(LevelManagementController::class)
        ->group(function () {
            // Liste
            Route::get('/', 'index')
                ->middleware('permission:view levels')
                ->name('index');

            // Create (AVANT /{level})
            Route::get('/create', 'create')
                ->middleware('permission:create levels')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('permission:create levels')
                ->name('store');

            // Modification
            Route::get('/{level}/edit', 'edit')
                ->middleware('permission:update levels')
                ->name('edit');

            Route::put('/{level}', 'update')
                ->middleware('permission:update levels')
                ->name('update');

            // Suppression
            Route::delete('/{level}', 'destroy')
                ->middleware('permission:delete levels')
                ->name('destroy');

            // Actions spécifiques
            Route::patch('/{level}/toggle-status', 'toggleStatus')
                ->middleware('permission:update levels')
                ->name('toggle-status');
        });

    // ========== ROLE & PERMISSION MANAGEMENT ==========
    Route::prefix('roles')
        ->name('roles.')
        ->controller(RolePermissionController::class)
        ->group(function () {
            // Liste
            Route::get('/', 'index')
                ->middleware('permission:view roles')
                ->name('index');

            // Create (AVANT /{role})
            Route::get('/create', 'create')
                ->middleware('permission:create roles')
                ->name('create');

            Route::post('/', 'store')
                ->middleware('permission:create roles')
                ->name('store');

            // Permissions (routes spécifiques AVANT /{role})
            Route::get('/permissions', 'permissionsIndex')
                ->middleware('permission:view permissions')
                ->name('permissions.index');

            Route::post('/permissions', 'storePermission')
                ->middleware('permission:create permissions')
                ->name('permissions.store');

            Route::delete('/permissions/{permission}', 'destroyPermission')
                ->middleware('permission:delete permissions')
                ->name('permissions.destroy');

            // Modification
            Route::get('/{role}/edit', 'edit')
                ->middleware('permission:update roles')
                ->name('edit');

            Route::put('/{role}', 'update')
                ->middleware('permission:update roles')
                ->name('update');

            // Suppression
            Route::delete('/{role}', 'destroy')
                ->middleware('permission:delete roles')
                ->name('destroy');

            // Actions spécifiques
            Route::post('/{role}/sync-permissions', 'syncPermissions')
                ->middleware('permission:assign permissions')
                ->name('sync-permissions');
        });
    // });
});
