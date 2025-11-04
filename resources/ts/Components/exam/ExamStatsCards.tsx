import StatCard from '@/Components/StatCard';
import {
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    MinusCircleIcon
} from '@heroicons/react/24/outline';
import { trans } from '@/utils/translations';

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
    const totalLabel = stats.total_students !== undefined
        ? trans('components.exam_stats_cards.total_students')
        : trans('components.exam_stats_cards.total_assigned');
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
                title={trans('components.exam_stats_cards.completed')}
                value={stats.completed}
                color="green"
                icon={CheckCircleIcon}
            />
            <StatCard
                title={trans('components.exam_stats_cards.in_progress')}
                value={stats.started}
                color="yellow"
                icon={ClockIcon}
            />
            <StatCard
                title={trans('components.exam_stats_cards.not_started')}
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
