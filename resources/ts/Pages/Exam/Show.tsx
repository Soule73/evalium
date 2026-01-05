import React, { useState, useMemo } from 'react';
import { Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { formatDuration } from '@/utils';
import { Button, ConfirmationModal, DataTable, ExamHeader, getGroupTableConfig, QuestionReadOnlySection, Section, StatCard } from '@/Components';
import { Toggle } from '@examena/ui';
import { Exam, Group } from '@/types';
import { ClockIcon, QuestionMarkCircleIcon, StarIcon, DocumentDuplicateIcon, UserGroupIcon, AcademicCapIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils';
import { groupsToPaginationType } from '@/utils';
import { trans } from '@/utils';
import { QuestionResultReadOnlyText, QuestionTeacherReadOnlyChoices } from '@/Components/features/exam/QuestionResultReadOnly';


interface Props {
    exam: Exam;
    assignedGroups: Group[];
}

const ExamShow: React.FC<Props> = ({ exam, assignedGroups }) => {
    const [isToggling, setIsToggling] = useState(false);
    const [isDuplicating, setIsDuplicating] = useState(false);
    const [showDuplicateModal, setShowDuplicateModal] = useState(false);
    const [removeGroupModal, setRemoveGroupModal] = useState<{ isOpen: boolean; group: Group | null }>({
        isOpen: false,
        group: null
    });

    const totalPoints = useMemo(() =>
        (exam.questions ?? []).reduce((sum, q) => sum + q.points, 0),
        [exam.questions]
    );

    const totalStudents = useMemo(() =>
        assignedGroups.reduce((sum, group) => sum + (group.active_students_count || 0), 0),
        [assignedGroups]
    );

    const questionsCount = (exam.questions ?? []).length;

    const handleRemoveGroup = () => {
        if (!removeGroupModal.group) return;

        router.delete(
            route('exams.groups.remove', {
                exam: exam.id,
                group: removeGroupModal.group.id,
            }),
            {
                onFinish: () => setRemoveGroupModal({ isOpen: false, group: null })
            }
        );
    };

    const groupsTableConfig = getGroupTableConfig({
        exam,
        showActions: true,
        showDetailsButton: true,
        onRemove: (group) => setRemoveGroupModal({ isOpen: true, group })
    });

    const handleToggleStatus = () => {
        if (isToggling) return;

        setIsToggling(true);
        router.patch(
            route('exams.toggle-active', exam.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsToggling(false),
            }
        );
    };

    const handleDuplicate = () => {
        if (isDuplicating) return;

        setIsDuplicating(true);
        router.post(
            route('exams.duplicate', exam.id),
            {},
            {
                onFinish: () => {
                    setIsDuplicating(false);
                    setShowDuplicateModal(false);
                },
            }
        );
    };

    return (
        <AuthenticatedLayout title={exam.title}
            breadcrumb={breadcrumbs.examShow(exam.title)}
        >
            <div className="max-w-6xl mx-auto space-y-6">
                <Section title={trans('exam_pages.show.title')}
                    actions={
                        <div className="flex flex-col md:flex-row space-y-2 md:space-x-3 md:space-y-0">
                            <Toggle
                                checked={exam.is_active}
                                onChange={handleToggleStatus}
                                disabled={isToggling}
                                color="green"
                                size="sm"
                                showLabel
                                activeLabel={trans('exam_pages.show.toggle_active')}
                                inactiveLabel={trans('exam_pages.show.toggle_inactive')}
                            />
                            <Button
                                onClick={() => setShowDuplicateModal(true)}
                                color="secondary"
                                variant='outline'
                                size="sm"
                                disabled={isDuplicating}
                            >
                                <DocumentDuplicateIcon className="h-4 w-4 mr-1" />
                                {trans('exam_pages.show.duplicate')}
                            </Button>
                            <Button
                                onClick={() => router.visit(route('exams.edit', exam.id))}
                                color="secondary"
                                variant='outline'
                                size="sm" >
                                {trans('exam_pages.show.edit')}
                            </Button>
                            <Button
                                onClick={() => router.visit(route('exams.groups', exam.id))}
                                color="secondary"
                                variant='outline'
                                size="sm" >
                                {trans('exam_pages.show.view_assignments')}
                            </Button>
                        </div>
                    }

                >
                    <div className="flex items-start justify-between">
                        <div className="flex-1">

                            <ExamHeader exam={exam} showDescription={true} showMetadata={false} />

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                                <StatCard
                                    title={trans('exam_pages.show.questions')}
                                    value={questionsCount}
                                    icon={QuestionMarkCircleIcon}
                                    color='blue'
                                />
                                <StatCard
                                    title={trans('exam_pages.show.total_points')}
                                    value={totalPoints}
                                    color='green'
                                    icon={StarIcon}
                                />
                                <StatCard
                                    title={trans('exam_pages.show.duration')}
                                    value={formatDuration(exam.duration)}
                                    color='yellow'
                                    icon={ClockIcon}
                                />
                                <StatCard
                                    title={trans('exam_pages.show.assigned_groups')}
                                    value={assignedGroups.length}
                                    color='purple'
                                    icon={UserGroupIcon}
                                />
                            </div>

                            {totalStudents > 0 && (
                                <div className="mt-4">
                                    <StatCard
                                        title={trans('exam_pages.show.concerned_students')}
                                        value={totalStudents}
                                        color='blue'
                                        icon={AcademicCapIcon}
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </Section>

                {/* Groupes assignés */}
                {assignedGroups && assignedGroups.length > 0 && (
                    <Section
                        title={trans('exam_pages.show.assigned_groups_section')}
                        subtitle={trans('exam_pages.show.assigned_groups_subtitle', { count: assignedGroups.length })}
                        collapsible
                        defaultOpen={true}
                    >
                        <DataTable
                            data={groupsToPaginationType(assignedGroups)}
                            config={groupsTableConfig}
                        />
                    </Section>
                )}

                {/* Questions */}
                <Section title={trans('exam_pages.show.exam_questions')} collapsible>
                    {(exam.questions ?? []).length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <p>{trans('exam_pages.show.no_questions')}</p>
                            <Link href={route('exams.edit', exam.id)} className="mt-2 inline-block">
                                <Button>{trans('exam_pages.show.add_questions')}</Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {(exam.questions ?? []).map((question, index) => (
                                <QuestionReadOnlySection key={question.id} question={question} questionIndex={index}>

                                    {question.type !== 'text' && (question.choices ?? []).length > 0 && (
                                        <div className="ml-4">
                                            <h5 className="text-sm font-medium text-gray-700 mb-2">
                                                {trans('exam_pages.show.answer_choices')}
                                            </h5>
                                            <div className="space-y-2">
                                                <QuestionTeacherReadOnlyChoices
                                                    type={question.type}
                                                    choices={question.choices ?? []}
                                                />
                                            </div>
                                        </div>
                                    )}

                                    {question.type === 'text' && (
                                        <QuestionResultReadOnlyText
                                            userText={trans('exam_pages.show.free_text_info')}
                                            label=""
                                        />
                                    )}

                                </QuestionReadOnlySection>
                            ))}
                        </div>
                    )}
                </Section>
            </div >

            <ConfirmationModal
                isOpen={showDuplicateModal}
                onClose={() => setShowDuplicateModal(false)}
                onConfirm={handleDuplicate}
                title={trans('exam_pages.show.duplicate_modal_title')}
                message={trans('exam_pages.show.duplicate_modal_message', { title: exam.title })}
                confirmText={trans('exam_pages.show.duplicate_confirm')}
                cancelText={trans('exam_pages.create.cancel')}
                type="info"
                loading={isDuplicating}
            />

            <ConfirmationModal
                isOpen={removeGroupModal.isOpen}
                onClose={() => setRemoveGroupModal({ isOpen: false, group: null })}
                onConfirm={handleRemoveGroup}
                title={trans('exam_pages.show.remove_group_title')}
                message={trans('exam_pages.show.remove_group_message',
                    { exam: exam.title, group: removeGroupModal.group?.display_name || '' })}
                confirmText={trans('exam_pages.show.remove')}
                cancelText={trans('exam_pages.create.cancel')}
                type="danger"
            />
        </AuthenticatedLayout >
    );
};

export default ExamShow;