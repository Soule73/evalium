import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type ClassModel,
    type Enrollment,
    type ClassSubject,
    type Assessment,
    type AssessmentAssignment,
    type PageProps,
    type User,
} from '@/types';
import { type PaginationType } from '@/types/datatable';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Section, Stat } from '@/Components';
import { ClassSubjectList, AssessmentList, EnrollmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    CalendarIcon,
} from '@heroicons/react/24/outline';

interface EnrollmentWithStudent extends Enrollment {
    student?: User;
}

interface Props extends PageProps {
    class: ClassModel;
    subjects: PaginationType<ClassSubject>;
    assessments: PaginationType<Assessment>;
    students: PaginationType<EnrollmentWithStudent>;
}

export default function TeacherClassShow({
    class: classItem,
    subjects,
    assessments,
    students,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            showSubtitle: t('teacher_class_pages.show.show_subtitle'),
            back: t('teacher_class_pages.show.back'),
            allAssessments: t('teacher_class_pages.show.all_assessments'),
            level: t('teacher_class_pages.show.level'),
            academicYear: t('teacher_class_pages.show.academic_year'),
            students: t('teacher_class_pages.show.students'),
            mySubjects: t('teacher_class_pages.show.my_subjects'),
            subjectsSectionTitle: t('teacher_class_pages.show.subjects_section_title'),
            subjectsSectionSubtitle: t('teacher_class_pages.show.subjects_section_subtitle'),
            recentAssessmentsTitle: t('teacher_class_pages.show.recent_assessments_title'),
            recentAssessmentsSubtitle: t('teacher_class_pages.show.recent_assessments_subtitle'),
            viewAll: t('teacher_class_pages.show.view_all'),
            studentsSectionTitle: t('teacher_class_pages.show.students_section_title'),
        }),
        [t],
    );

    const studentsSectionSubtitleTranslation = useMemo(
        () => t('teacher_class_pages.show.students_section_subtitle', { count: students.total }),
        [t, students.total],
    );

    const handleBack = () => {
        router.visit(route('teacher.classes.index'));
    };

    const handleViewAssessments = () => {
        router.visit(route('teacher.assessments.index', { class_id: classItem.id }));
    };

    const handleCreateAssessment = (classSubject: ClassSubject) => {
        router.visit(route('teacher.assessments.create', { class_subject_id: classSubject.id }));
    };

    const handleViewAssessment = (item: Assessment | AssessmentAssignment) => {
        const assessment = item as Assessment;
        router.visit(route('teacher.assessments.show', assessment.id));
    };

    return (
        <AuthenticatedLayout
            title={classItem.display_name ?? classItem.name}
            breadcrumb={breadcrumbs.teacher.showClass(classItem)}
        >
            <div className="space-y-6">
                <Section
                    title={classItem.display_name ?? classItem.name}
                    subtitle={translations.showSubtitle}
                    actions={
                        <div className="flex space-x-3">
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={handleBack}
                            >
                                {translations.back}
                            </Button>
                            <Button
                                size="sm"
                                variant="solid"
                                color="primary"
                                onClick={handleViewAssessments}
                            >
                                {translations.allAssessments}
                            </Button>
                        </div>
                    }
                >
                    <Stat.Group columns={4}>
                        <Stat.Item
                            icon={AcademicCapIcon}
                            title={translations.level}
                            value={
                                <span className="text-sm text-gray-900">
                                    {classItem.level?.name || '-'}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={CalendarIcon}
                            title={translations.academicYear}
                            value={
                                <span className="text-sm text-gray-900">
                                    {classItem.academic_year?.name || '-'}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={UserGroupIcon}
                            title={translations.students}
                            value={
                                <span className="text-sm font-semibold text-gray-900">
                                    {students.total}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={BookOpenIcon}
                            title={translations.mySubjects}
                            value={
                                <span className="text-sm font-semibold text-gray-900">
                                    {subjects.total}
                                </span>
                            }
                        />
                    </Stat.Group>
                </Section>

                <Section
                    title={translations.subjectsSectionTitle}
                    subtitle={translations.subjectsSectionSubtitle}
                >
                    <ClassSubjectList
                        data={subjects}
                        variant="teacher"
                        showClassColumn={false}
                        showTeacherColumn={false}
                        onCreateAssessment={handleCreateAssessment}
                    />
                </Section>

                <Section
                    title={translations.recentAssessmentsTitle}
                    subtitle={translations.recentAssessmentsSubtitle}
                    actions={
                        assessments.total > 0 && (
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={handleViewAssessments}
                            >
                                {translations.viewAll}
                            </Button>
                        )
                    }
                >
                    <AssessmentList
                        data={assessments}
                        variant="teacher"
                        showClassColumn={false}
                        onView={handleViewAssessment}
                    />
                </Section>

                <Section
                    title={translations.studentsSectionTitle}
                    subtitle={studentsSectionSubtitleTranslation}
                >
                    <EnrollmentList
                        data={students as PaginationType<Enrollment>}
                        variant="teacher"
                        showClassColumn={false}
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
