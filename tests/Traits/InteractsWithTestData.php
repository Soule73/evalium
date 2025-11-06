<?php

namespace Tests\Traits;

trait InteractsWithTestData
{
    use CreatesTestUsers;
    use CreatesTestExams;
    use CreatesTestGroups;
    use CreatesTestAssignments;

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
        $exam = $this->createExamWithQuestions($teacher);
        $students = $this->createMultipleStudents($studentCount);

        return (object) [
            'teacher' => $teacher,
            'exam' => $exam,
            'students' => $students,
        ];
    }

    protected function setupGroupTestData(int $studentsPerGroup = 2, int $groupCount = 1): object
    {
        $this->seedRolesAndPermissions();

        $groups = [];

        for ($i = 0; $i < $groupCount; $i++) {
            $groups[] = $this->createGroupWithStudents($studentsPerGroup);
        }

        return (object) [
            'groups' => $groups,
            'group' => $groups[0],
        ];
    }

    protected function setupCompleteExamScenario(): object
    {
        $this->seedRolesAndPermissions();

        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher);
        $group = $this->createGroupWithStudents(3);

        $this->assignExamToGroup($exam, $group, $teacher);

        $students = $group->students;
        $assignments = [];

        foreach ($students as $index => $student) {
            if ($index === 0) {
                $assignments[] = $this->createStartedAssignment($exam, $student);
            } elseif ($index === 1) {
                $assignments[] = $this->createSubmittedAssignment($exam, $student);
            } else {
                $assignments[] = $this->createGradedAssignment($exam, $student);
            }
        }

        return (object) [
            'teacher' => $teacher,
            'exam' => $exam,
            'group' => $group,
            'students' => $students,
            'assignments' => $assignments,
        ];
    }
}
