import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import { Exam, User, Group } from '@/types';
import { route } from 'ziggy-js';
import { UserGroupIcon, UserIcon, UserPlusIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import ConfirmationModal from '@/Components/ConfirmationModal';

interface Props {
    exam: Exam;
    students: User[];
    assignedGroups: Group[];
    availableGroups: Group[];
}

export default function ExamAssign({ exam, students, assignedGroups, availableGroups }: Props) {
    const [activeTab, setActiveTab] = useState<'groups' | 'students'>('groups');
    const [isProcessing, setIsProcessing] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [pendingAssignment, setPendingAssignment] = useState<{
        type: 'groups' | 'students';
        ids: (string | number)[];
        count: number;
    } | null>(null);

    const handleAssignGroups = (selectedIds: (string | number)[]) => {
        const selectedGroups = availableGroups.filter(g => selectedIds.includes(g.id));
        const totalStudents = selectedGroups.reduce((sum, group) => sum + (group.active_students_count || 0), 0);

        setPendingAssignment({
            type: 'groups',
            ids: selectedIds,
            count: totalStudents
        });
        setShowConfirmModal(true);
    };

    const handleAssignStudents = (selectedIds: (string | number)[]) => {
        setPendingAssignment({
            type: 'students',
            ids: selectedIds,
            count: selectedIds.length
        });
        setShowConfirmModal(true);
    };

    const confirmAssignment = () => {
        if (!pendingAssignment) return;

        setIsProcessing(true);
        const routeName = pendingAssignment.type === 'groups'
            ? 'teacher.exams.assign.groups'
            : 'teacher.exams.assign.store';

        const dataKey = pendingAssignment.type === 'groups' ? 'group_ids' : 'student_ids';

        router.post(
            route(routeName, exam.id),
            { [dataKey]: pendingAssignment.ids },
            {
                onFinish: () => {
                    setIsProcessing(false);
                    setShowConfirmModal(false);
                    setPendingAssignment(null);
                }
            }
        );
    };

    const cancelAssignment = () => {
        setShowConfirmModal(false);
        setPendingAssignment(null);
    };

    // Transformer availableGroups en format PaginationType
    const groupsData: PaginationType<Group> = {
        data: availableGroups,
        current_page: 1,
        per_page: availableGroups.length,
        total: availableGroups.length,
        last_page: 1,
        from: 1,
        to: availableGroups.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };

    // Transformer students en format PaginationType
    const studentsData: PaginationType<User> = {
        data: students,
        current_page: 1,
        per_page: students.length,
        total: students.length,
        last_page: 1,
        from: 1,
        to: students.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };

    const groupsTableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'display_name',
                label: 'Groupe',
                render: (group) => (
                    <div className="flex items-center space-x-3">
                        <UserGroupIcon className="h-5 w-5 text-gray-400 shrink-0" />
                        <div>
                            <div className="font-medium text-gray-900">{group.display_name}</div>
                            <div className="text-sm text-gray-500">
                                {group.active_students_count} étudiant(s) actif(s)
                            </div>
                        </div>
                    </div>
                )
            }
        ],
        searchPlaceholder: 'Rechercher un groupe...',
        emptyState: {
            title: 'Tous les groupes assignés',
            subtitle: 'Tous les groupes actifs ont déjà accès à cet examen',
            icon: 'UserGroupIcon'
        },
        emptySearchState: {
            title: 'Aucun groupe trouvé',
            subtitle: 'Aucun groupe ne correspond à vos critères de recherche.',
            resetLabel: 'Réinitialiser la recherche'
        },
        perPageOptions: [10, 25, 50],
        enableSelection: true,
        selectionActions: (selectedIds) => (
            <Button
                onClick={() => handleAssignGroups(selectedIds)}
                color="primary"
                size="sm"
                loading={isProcessing}
                disabled={isProcessing || selectedIds.length === 0}
            >
                <UserPlusIcon className="w-4 h-4 mr-2" />
                Assigner à {selectedIds.length} groupe{selectedIds.length > 1 ? 's' : ''}
            </Button>
        ),
    };

    const studentsTableConfig: DataTableConfig<User> = {
        columns: [
            {
                key: 'name',
                label: 'Étudiant',
                render: (student) => (
                    <div>
                        <div className="font-medium text-gray-900">{student.name}</div>
                        <div className="text-sm text-gray-500">{student.email}</div>
                    </div>
                )
            }
        ],
        searchPlaceholder: 'Rechercher un étudiant...',
        emptyState: {
            title: 'Aucun étudiant disponible',
            subtitle: 'Tous les étudiants ont déjà accès à cet examen',
            icon: 'UserIcon'
        },
        emptySearchState: {
            title: 'Aucun étudiant trouvé',
            subtitle: 'Aucun étudiant ne correspond à vos critères de recherche.',
            resetLabel: 'Réinitialiser la recherche'
        },
        perPageOptions: [10, 25, 50],
        enableSelection: true,
        selectionActions: (selectedIds) => (
            <Button
                onClick={() => handleAssignStudents(selectedIds)}
                color="primary"
                size="sm"
                loading={isProcessing}
                disabled={isProcessing || selectedIds.length === 0}
            >
                <UserPlusIcon className="w-4 h-4 mr-2" />
                Assigner à {selectedIds.length} étudiant{selectedIds.length > 1 ? 's' : ''}
            </Button>
        ),
    };

    return (
        <AuthenticatedLayout title={`Assigner l'examen: ${exam.title}`}>

            <Section
                title="Informations sur l'examen"
                subtitle="Détails de l'examen à assigner"
                actions={<Button
                    type="button"
                    onClick={() => router.visit(route('teacher.exams.show', exam.id))}
                    color="secondary"
                    variant="outline"
                >
                    Annuler
                </Button>}
            >
                <div className="space-y-2">
                    <h2 className="text-xl font-semibold text-gray-900">{exam.title}</h2>
                    {exam.description && (
                        <p className="text-gray-600">{exam.description}</p>
                    )}
                    <p className="text-sm text-gray-500">
                        Durée: {exam.duration} minutes
                    </p>
                </div>
            </Section>

            {/* Groupes déjà assignés */}
            {assignedGroups.length > 0 && (
                <Section
                    title="Groupes assignés"
                    subtitle={`${assignedGroups.length} groupe(s) ont accès à cet examen`}
                    collapsible
                    defaultOpen={false}
                >
                    <div className="space-y-2">
                        {assignedGroups.map((group) => (
                            <div key={group.id} className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div className="flex items-center space-x-3">
                                    <UserGroupIcon className="h-5 w-5 text-green-600" />
                                    <div>
                                        <div className="font-medium text-gray-900">{group.display_name}</div>
                                        <div className="text-sm text-gray-500">
                                            {group.active_students_count} étudiant(s) actif(s)
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    color="danger"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        if (confirm('Êtes-vous sûr de vouloir retirer cet examen de ce groupe ?')) {
                                            router.delete(route('teacher.exams.groups.remove', { exam: exam.id, group: group.id }));
                                        }
                                    }}
                                >
                                    Retirer
                                </Button>
                            </div>
                        ))}
                    </div>
                </Section>
            )}

            {/* Tabs pour choisir entre groupes et étudiants individuels */}
            <div className="border-b border-gray-200 bg-white rounded-t-lg">
                <nav className="-mb-px flex space-x-8 px-6">
                    <button
                        onClick={() => setActiveTab('groups')}
                        className={`${activeTab === 'groups'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
                    >
                        <UserGroupIcon className="h-5 w-5" />
                        <span>Assigner par groupes ({availableGroups.length})</span>
                    </button>
                    <button
                        onClick={() => setActiveTab('students')}
                        className={`${activeTab === 'students'
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2`}
                    >
                        <UserIcon className="h-5 w-5" />
                        <span>Assigner individuellement ({students.length})</span>
                    </button>
                </nav>
            </div>

            {/* Contenu des tabs */}
            {activeTab === 'groups' ? (
                <Section
                    title="Sélectionner les groupes"
                    subtitle="Sélectionnez les groupes auxquels vous souhaitez donner accès à cet examen"
                >
                    <DataTable
                        data={groupsData}
                        config={groupsTableConfig}
                    />
                </Section>
            ) : (
                <Section
                    title="Sélectionner les étudiants"
                    subtitle="Assignation individuelle pour des cas particuliers"
                >
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p className="text-sm text-blue-800">
                            <strong>Recommandation:</strong> Privilégiez l'assignation par groupes pour une gestion plus efficace.
                            L'assignation individuelle est utile pour des cas particuliers.
                        </p>
                    </div>

                    <DataTable
                        data={studentsData}
                        config={studentsTableConfig}
                    />
                </Section>
            )}

            {/* Modal de confirmation */}
            <ConfirmationModal
                isOpen={showConfirmModal}
                onClose={cancelAssignment}
                onConfirm={confirmAssignment}
                title="Confirmer l'assignation"
                message={
                    pendingAssignment?.type === 'groups'
                        ? `Vous êtes sur le point d'assigner cet examen à ${pendingAssignment.ids.length} groupe(s), ce qui donnera accès à environ ${pendingAssignment.count} étudiant(s).`
                        : `Vous êtes sur le point d'assigner cet examen à ${pendingAssignment?.count} étudiant(s) individuellement.`
                }
                confirmText="Confirmer l'assignation"
                cancelText="Annuler"
                type="info"
                icon={pendingAssignment?.type === 'groups' ? UserGroupIcon : UserIcon}
                loading={isProcessing}
            >
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 w-full">
                    <p className="text-sm text-blue-800">
                        <strong>Examen :</strong> {exam.title}
                    </p>
                    {exam.description && (
                        <p className="text-sm text-blue-700 mt-1">{exam.description}</p>
                    )}
                    <p className="text-sm text-blue-700 mt-1">
                        <strong>Durée :</strong> {exam.duration} minutes
                    </p>
                </div>
            </ConfirmationModal>
        </AuthenticatedLayout>
    );
}