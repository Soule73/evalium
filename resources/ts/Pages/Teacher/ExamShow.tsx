import React, { useState, useMemo } from 'react';
import { Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDuration } from '@/utils/formatters';
import { Button } from '@/Components';
import { Exam, Group } from '@/types';
import StatCard from '@/Components/StatCard';
import { ClockIcon, QuestionMarkCircleIcon, StarIcon, DocumentDuplicateIcon, UserGroupIcon, AcademicCapIcon } from '@heroicons/react/24/outline';
import Section from '@/Components/Section';
import { route } from 'ziggy-js';
import QuestionReadOnlySection from '@/Components/exam/QuestionReadOnlySection';
import { QuestionResultReadOnlyText, QuestionTeacherReadOnlyChoices } from '@/Components/exam/QuestionResultReadOnly';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { DataTable } from '@/Components/DataTable';
import { getGroupTableConfig, ExamHeader } from '@/Components/exam';
import { groupsToPaginationType } from '@/utils';
import Toggle from '@/Components/form/Toggle';
import ConfirmationModal from '@/Components/ConfirmationModal';


interface Props {
    exam: Exam;
    assignedGroups: Group[];
}

const TeacherExamShow: React.FC<Props> = ({ exam, assignedGroups }) => {
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
            route('teacher.exams.groups.remove', {
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
            route('teacher.exams.toggle-active', exam.id),
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
            route('teacher.exams.duplicate', exam.id),
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
            breadcrumb={breadcrumbs.teacherExamShow(exam.title)}
        >
            <div className="max-w-6xl mx-auto space-y-6">
                <Section title={"Détails et gestion de l'examen"}
                    actions={
                        <div className="flex flex-col md:flex-row space-y-2 md:space-x-3 md:space-y-0">
                            <Toggle
                                checked={exam.is_active}
                                onChange={handleToggleStatus}
                                disabled={isToggling}
                                color="green"
                                size="sm"
                                showLabel
                                activeLabel="Actif"
                                inactiveLabel="Inactif"
                            />
                            <Button
                                onClick={() => setShowDuplicateModal(true)}
                                color="secondary"
                                variant='outline'
                                size="sm"
                                disabled={isDuplicating}
                            >
                                <DocumentDuplicateIcon className="h-4 w-4 mr-1" />
                                Dupliquer
                            </Button>
                            <Button
                                onClick={() => router.visit(route('teacher.exams.edit', exam.id))}
                                color="secondary"
                                variant='outline'
                                size="sm" >
                                Modifier
                            </Button>
                            <Button
                                onClick={() => router.visit(route('teacher.exams.assignments', exam.id))}
                                color="secondary"
                                variant='outline'
                                size="sm" >
                                Voir les assignations
                            </Button>
                        </div>
                    }

                >
                    <div className="flex items-start justify-between">
                        <div className="flex-1">

                            <ExamHeader exam={exam} showDescription={true} showMetadata={false} />

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                                <StatCard
                                    title="Questions"
                                    value={questionsCount}
                                    icon={QuestionMarkCircleIcon}
                                    color='blue'
                                />
                                <StatCard
                                    title="Points totaux"
                                    value={totalPoints}
                                    color='green'
                                    icon={StarIcon}
                                />
                                <StatCard
                                    title="Durée"
                                    value={formatDuration(exam.duration)}
                                    color='yellow'
                                    icon={ClockIcon}
                                />
                                <StatCard
                                    title="Groupes assignés"
                                    value={assignedGroups.length}
                                    color='purple'
                                    icon={UserGroupIcon}
                                />
                            </div>

                            {totalStudents > 0 && (
                                <div className="mt-4">
                                    <StatCard
                                        title="Étudiants concernés"
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
                        title="Groupes assignés"
                        subtitle={`${assignedGroups.length} groupe(s) ont accès à cet examen`}
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
                <Section title="Questions de l'examen" collapsible>
                    {(exam.questions ?? []).length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <p>Aucune question ajoutée à cet examen.</p>
                            <Link href={route('teacher.exams.edit', exam.id)} className="mt-2 inline-block">
                                <Button>Ajouter des questions</Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {(exam.questions ?? []).map((question, index) => (
                                <QuestionReadOnlySection key={question.id} question={question} questionIndex={index}>

                                    {question.type !== 'text' && (question.choices ?? []).length > 0 && (
                                        <div className="ml-4">
                                            <h5 className="text-sm font-medium text-gray-700 mb-2">
                                                Choix de réponse :
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
                                            userText={"Question à réponse libre - correction manuelle requise"}
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
                title="Dupliquer l'examen"
                message={`Voulez-vous vraiment dupliquer l'examen "${exam.title}" ? Une copie sera créée avec toutes les questions.`}
                confirmText="Dupliquer"
                cancelText="Annuler"
                type="info"
                loading={isDuplicating}
            />

            <ConfirmationModal
                isOpen={removeGroupModal.isOpen}
                onClose={() => setRemoveGroupModal({ isOpen: false, group: null })}
                onConfirm={handleRemoveGroup}
                title="Retirer le groupe"
                message={`Êtes-vous sûr de vouloir retirer l'examen "${exam.title}" du groupe "${removeGroupModal.group?.display_name}" ?`}
                confirmText="Retirer"
                cancelText="Annuler"
                type="danger"
            />
        </AuthenticatedLayout >
    );
};

export default TeacherExamShow;