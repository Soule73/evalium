<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed deterministic data for E2E tests.
 *
 * Creates a minimal but complete dataset matching the credentials
 * defined in e2e/.env so that authentication setup projects and
 * role-specific test suites can run reliably.
 */
class E2ESeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $hashed = Hash::make(self::PASSWORD);

        $academicYear = $this->seedAcademicYear();
        $semesters = $this->seedSemesters($academicYear);
        $levels = $this->seedLevels();
        $users = $this->seedUsers($hashed);
        $subjects = $this->seedSubjects($levels);
        $classes = $this->seedClasses($academicYear, $levels);

        $this->seedEnrollments($classes, $users['students']);

        $classSubjects = $this->seedClassSubjects(
            $classes,
            $subjects,
            $users['teacher'],
            $semesters['s1'],
            $levels
        );

        $this->seedAssessments($classSubjects, $users['teacher']);
    }

    private function seedAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
    }

    /**
     * @return array{s1: Semester, s2: Semester}
     */
    private function seedSemesters(AcademicYear $year): array
    {
        $s1 = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Semestre 1',
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
            'order_number' => 1,
        ]);

        $s2 = Semester::create([
            'academic_year_id' => $year->id,
            'name' => 'Semestre 2',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'order_number' => 2,
        ]);

        return ['s1' => $s1, 's2' => $s2];
    }

    /**
     * @return array{l1: Level}
     */
    private function seedLevels(): array
    {
        $l1 = Level::create([
            'name' => 'L1',
            'code' => 'L1',
            'description' => 'Licence 1',
            'order' => 1,
            'is_active' => true,
        ]);

        return ['l1' => $l1];
    }

    /**
     * @return array{admin: User, teacher: User, students: array<User>}
     */
    private function seedUsers(string $hashedPassword): array
    {
        $base = [
            'password' => $hashedPassword,
            'email_verified_at' => now(),
            'is_active' => true,
        ];

        $admin = User::create(array_merge($base, [
            'name' => 'Admin E2E',
            'email' => 'admin@evalium.test',
        ]));
        $admin->assignRole('super_admin');

        $teacher = User::create(array_merge($base, [
            'name' => 'Marie Dupont',
            'email' => 'teacher@evalium.test',
        ]));
        $teacher->assignRole('teacher');

        $studentNames = [
            ['name' => 'Alice Martin', 'email' => 'student@evalium.test'],
            ['name' => 'Bob Durand', 'email' => 'bob.durand@evalium.test'],
            ['name' => 'Clara Bernard', 'email' => 'clara.bernard@evalium.test'],
        ];

        $students = [];
        foreach ($studentNames as $data) {
            $student = User::create(array_merge($base, $data));
            $student->assignRole('student');
            $students[] = $student;
        }

        return ['admin' => $admin, 'teacher' => $teacher, 'students' => $students];
    }

    /**
     * @param  array{l1: Level}  $levels
     * @return array{math: Subject, physics: Subject}
     */
    private function seedSubjects(array $levels): array
    {
        $math = Subject::create([
            'level_id' => $levels['l1']->id,
            'name' => 'Mathematics',
            'code' => 'MATH_L1',
            'description' => 'Fundamental mathematics',
        ]);

        $physics = Subject::create([
            'level_id' => $levels['l1']->id,
            'name' => 'Physics',
            'code' => 'PHYS_L1',
            'description' => 'General physics',
        ]);

        return ['math' => $math, 'physics' => $physics];
    }

    /**
     * @param  array{l1: Level}  $levels
     * @return array{classA: ClassModel}
     */
    private function seedClasses(AcademicYear $year, array $levels): array
    {
        $classA = ClassModel::create([
            'academic_year_id' => $year->id,
            'level_id' => $levels['l1']->id,
            'name' => 'L1 - Group A',
            'max_students' => 30,
        ]);

        return ['classA' => $classA];
    }

    /**
     * @param  array{classA: ClassModel}  $classes
     * @param  array<User>  $students
     */
    private function seedEnrollments(array $classes, array $students): void
    {
        foreach ($students as $student) {
            Enrollment::create([
                'class_id' => $classes['classA']->id,
                'student_id' => $student->id,
                'enrolled_at' => now()->subMonths(3),
                'status' => EnrollmentStatus::Active,
            ]);
        }
    }

    /**
     * @param  array{classA: ClassModel}  $classes
     * @param  array{math: Subject, physics: Subject}  $subjects
     * @param  array{l1: Level}  $levels
     * @return array<ClassSubject>
     */
    private function seedClassSubjects(
        array $classes,
        array $subjects,
        User $teacher,
        Semester $semester,
        array $levels,
    ): array {
        $result = [];

        foreach ($subjects as $subject) {
            $result[] = ClassSubject::create([
                'class_id' => $classes['classA']->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'semester_id' => $semester->id,
                'coefficient' => 3.0,
                'valid_from' => now()->subMonths(3),
            ]);
        }

        return $result;
    }

    /**
     * @param  array<ClassSubject>  $classSubjects
     */
    private function seedAssessments(array $classSubjects, User $teacher): void
    {
        foreach ($classSubjects as $cs) {
            Assessment::create([
                'class_subject_id' => $cs->id,
                'teacher_id' => $teacher->id,
                'title' => "Exam - {$cs->subject->name}",
                'type' => AssessmentType::Exam,
                'delivery_mode' => DeliveryMode::Supervised,
                'coefficient' => 2.0,
                'duration_minutes' => 60,
                'scheduled_at' => now()->addDays(7),
                'due_date' => now()->addDays(7),
                'is_published' => true,
            ]);

            Assessment::create([
                'class_subject_id' => $cs->id,
                'teacher_id' => $teacher->id,
                'title' => "Quiz - {$cs->subject->name}",
                'type' => AssessmentType::Quiz,
                'delivery_mode' => DeliveryMode::Supervised,
                'coefficient' => 1.0,
                'duration_minutes' => 20,
                'scheduled_at' => now()->addDays(14),
                'due_date' => now()->addDays(14),
                'is_published' => false,
            ]);
        }
    }
}
