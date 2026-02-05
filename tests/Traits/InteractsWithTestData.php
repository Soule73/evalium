<?php

namespace Tests\Traits;

trait InteractsWithTestData
{
    use CreatesTestAssessments;
    use CreatesTestAssignments;
    use CreatesTestClasses;
    use CreatesTestUsers;

    protected function setupBasicTestData(): object
    {
        $this->seedRolesAndPermissions();

        return (object) [
            'admin' => $this->createAdmin(),
            'teacher' => $this->createTeacher(),
            'student' => $this->createStudent(),
        ];
    }

    protected function setupExamTestData(int $studentCount = 2): object
    {
        $this->seedRolesAndPermissions();

        $teacher = $this->createTeacher();
        $assessment = $this->createAssessmentWithQuestions($teacher);
        $students = $this->createMultipleStudents($studentCount);

        return (object) [
            'teacher' => $teacher,
            'assessment' => $assessment,
            'students' => $students,
        ];
    }

    protected function setupGroupTestData(int $studentsPerClass = 2, int $classCount = 1): object
    {
        $this->seedRolesAndPermissions();

        $classes = [];

        for ($i = 0; $i < $classCount; $i++) {
            $classes[] = $this->createClassWithStudents($studentsPerClass);
        }

        return (object) [
            'classes' => $classes,
            'class' => $classes[0],
        ];
    }

    protected function setupCompleteExamScenario(): object
    {
        $this->seedRolesAndPermissions();

        $teacher = $this->createTeacher();
        $assessment = $this->createAssessmentWithQuestions($teacher);
        $class = $this->createClassWithStudents(3);

        $this->assignAssessmentToClass($assessment, $class, $teacher);

        $students = $class->students;
        $assignments = [];

        foreach ($students as $index => $student) {
            if ($index === 0) {
                $assignments[] = $this->createStartedAssignment($assessment, $student);
            } elseif ($index === 1) {
                $assignments[] = $this->createSubmittedAssignment($assessment, $student);
            } else {
                $assignments[] = $this->createGradedAssignment($assessment, $student);
            }
        }

        return (object) [
            'teacher' => $teacher,
            'assessment' => $assessment,
            'class' => $class,
            'students' => $students,
            'assignments' => $assignments,
        ];
    }
}
