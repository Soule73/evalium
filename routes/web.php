<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Exam\ResultsController;
use App\Http\Controllers\Exam\AssignmentController;
use App\Http\Controllers\Exam\CorrectionController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Exam\GroupAssignmentController;
use App\Http\Controllers\Admin\LevelManagementController;
use App\Http\Controllers\Student\ExamController as StudentExamController;

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

    // ==================== ROUTES STRICTEMENT RÉSERVÉES AU RÔLE "STUDENT" ====================
    // Ces actions nécessitent hasRole('student') - Ne peuvent PAS être assignées à d'autres rôles
    Route::middleware('role:student')
        ->prefix('student')
        ->name('student.')
        ->group(function () {
            Route::get('/dashboard/student', [DashboardController::class, 'student'])
                ->name('dashboard');

            Route::controller(StudentExamController::class)
                ->prefix('exams')
                ->name('exams.')
                ->group(function () {
                    // Passer un examen - STRICT student only
                    Route::get('/{exam}/take', 'take')
                        ->name('take');

                    // Sauvegarder les réponses en cours - STRICT student only
                    Route::post('/{exam}/save-answers', 'saveAnswers')
                        ->name('save-answers');

                    // Gérer les violations de sécurité - STRICT student only
                    Route::post('/{exam}/security-violation', 'handleSecurityViolation')
                        ->name('security-violation');

                    // Abandonner un examen - STRICT student only
                    Route::post('/{exam}/abandon', 'abandon')
                        ->name('abandon');

                    // Soumettre un examen - STRICT student only
                    Route::post('/{exam}/submit', 'submit')
                        ->name('submit');
                });
        });

    // ==================== ROUTES UNIFIÉES BASÉES SUR PERMISSIONS ====================
    // Accessibles à TOUS les utilisateurs ayant les permissions appropriées
    // Interface dynamique selon les permissions de l'utilisateur

    // Liste des examens - Accessible selon 'view exams'
    Route::get('/exams', [ExamController::class, 'index'])
        ->middleware('permission:view exams')
        ->name('exams.index');

    // Voir un examen - Accessible selon 'view exams'
    Route::get('/exams/{exam}', [ExamController::class, 'show'])
        ->middleware('permission:view exams')
        ->name('exams.show');

    // Créer un examen - Permission 'create exams'
    Route::get('/exams/create', [ExamController::class, 'create'])
        ->middleware('permission:create exams')
        ->name('exams.create');

    Route::post('/exams', [ExamController::class, 'store'])
        ->middleware('permission:create exams')
        ->name('exams.store');

    // Modifier un examen - Permission 'update exams'
    Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])
        ->middleware('permission:update exams')
        ->name('exams.edit');

    Route::put('/exams/{exam}', [ExamController::class, 'update'])
        ->middleware('permission:update exams')
        ->name('exams.update');

    // Supprimer un examen - Permission 'delete exams'
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])
        ->middleware('permission:delete exams')
        ->name('exams.destroy');

    // Dupliquer un examen - Permission 'create exams'
    Route::post('/exams/{exam}/duplicate', [ExamController::class, 'duplicate'])
        ->middleware('permission:create exams')
        ->name('exams.duplicate');

    // Publier/Dépublier un examen - Permission 'publish exams'
    Route::patch('/exams/{exam}/toggle-active', [ExamController::class, 'toggleActive'])
        ->middleware('permission:publish exams')
        ->name('exams.toggle-active');

    // Assignations d'examens
    Route::controller(AssignmentController::class)
        ->prefix('exams')
        ->group(function () {
            Route::get('/{exam}/assign', 'showAssignForm')
                ->middleware('permission:assign exams')
                ->name('exams.assign');

            Route::post('/{exam}/assign', 'assignToStudents')
                ->middleware('permission:create assignments')
                ->name('exams.assign.store');

            Route::get('/{exam}/assignments', 'showAssignments')
                ->middleware('permission:view assignments')
                ->name('exams.assignments');

            Route::delete('/{exam}/assignments/{user}', 'removeAssignment')
                ->middleware('permission:delete assignments')
                ->name('exams.assignment.remove');
        });

    // Assignations de groupes
    Route::controller(GroupAssignmentController::class)
        ->prefix('exams')
        ->group(function () {
            Route::post('/{exam}/assign-groups', 'assignToGroups')
                ->middleware('permission:assign group exams')
                ->name('exams.assign.groups');

            Route::delete('/{exam}/groups/{group}', 'removeFromGroup')
                ->middleware('permission:assign group exams')
                ->name('exams.groups.remove');

            Route::get('/{exam}/groups/{group}/details', 'showGroupDetails')
                ->middleware('permission:view assignments')
                ->name('exams.group-details');
        });

    // Correction d'examens
    Route::controller(CorrectionController::class)
        ->prefix('exams')
        ->group(function () {
            Route::get('/{exam}/review/{student}', 'showStudentReview')
                ->middleware('permission:correct exams')
                ->name('exams.review');

            Route::post('/{exam}/review/{student}', 'saveStudentReview')
                ->middleware('permission:grade answers')
                ->name('exams.review.save');

            Route::post('/{exam}/score/update', 'updateScore')
                ->middleware('permission:grade assignments')
                ->name('exams.score.update');
        });

    // Résultats et statistiques
    Route::controller(ResultsController::class)
        ->prefix('exams')
        ->group(function () {
            Route::get('/{exam}/results/{student}', 'showStudentResults')
                ->middleware('permission:view exam results')
                ->name('exams.results');

            Route::get('/{exam}/stats', 'stats')
                ->middleware('permission:view reports')
                ->name('exams.stats');
        });

    // ==================== DASHBOARDS SPÉCIFIQUES PAR RÔLE ====================
    // Dashboards qui restent séparés car ils affichent des interfaces différentes
    Route::get('/dashboard/teacher', [DashboardController::class, 'teacher'])
        ->middleware('permission:view teacher dashboard')
        ->name('teacher.dashboard');

    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
        ->middleware('permission:view admin dashboard')
        ->name('admin.dashboard');

    // ==================== ADMIN ROUTES ====================
    Route::prefix('admin')
        ->group(function () {
            // User Management Routes
            Route::prefix('/users')
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

            // Group Management Routes
            Route::prefix('/groups')
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

            // Level Management Routes
            Route::prefix('/levels')
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
                        ->middleware('permission:manage levels')
                        ->name('toggle-status');
                });

            // Role & Permission Management Routes
            Route::prefix('/roles')
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

                    // Permissions routes
                    Route::get('/permissions', 'permissionsIndex')
                        ->middleware('permission:view permissions')
                        ->name('permissions.index');

                    Route::post('/permissions', 'storePermission')
                        ->middleware('permission:create permissions')
                        ->name('permissions.store');

                    Route::delete('/permissions/{permission}', 'destroyPermission')
                        ->middleware('permission:delete permissions')
                        ->name('permissions.destroy');
                });
        });
});
