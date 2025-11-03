import StatCard from '@/Components/StatCard';
import {
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    MinusCircleIcon
} from '@heroicons/react/24/outline';

interface ExamStats {
    total_students?: number;
    total_assigned?: number;
    completed: number;
    started: number;
    assigned: number;
    average_score: number | null;
}

interface ExamStatsCardsProps {
    stats: ExamStats;
    className?: string;
}

/**
 * Composant réutilisable pour afficher les statistiques d'un examen
 * Utilisé dans ExamAssignments et ExamGroupDetails
 */
export default function ExamStatsCards({ stats, className = '' }: ExamStatsCardsProps) {
    const totalLabel = stats.total_students !== undefined ? 'Total étudiants' : 'Total assigné';
    const totalValue = stats.total_students ?? stats.total_assigned ?? 0;

    return (
        <div className={`grid grid-cols-1 md:grid-cols-4 gap-4 ${className}`}>
            <StatCard
                title={totalLabel}
                value={totalValue}
                color="blue"
                icon={UserGroupIcon}
            />
            <StatCard
                title="Terminé"
                value={stats.completed}
                color="green"
                icon={CheckCircleIcon}
            />
            <StatCard
                title="En cours"
                value={stats.started}
                color="yellow"
                icon={ClockIcon}
            />
            <StatCard
                title="Non commencé"
                value={stats.assigned}
                color="purple"
                icon={MinusCircleIcon}
            />
            {/* Note: Vous pouvez ajouter une 5ème carte pour la note moyenne si nécessaire */}
            {/* <StatCard
                title="Note moyenne"
                value={stats.average_score !== null ? `${Math.round(stats.average_score)}%` : 'N/A'}
                color="purple"
                icon={ChartBarIcon}
            /> */}
        </div>
    );
}
