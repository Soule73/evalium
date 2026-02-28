import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { CHART_DEFAULTS, COMPLETION_STATUS_COLORS } from './chartTheme';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface CompletionDataItem {
    name: string;
    graded: number;
    submitted: number;
    in_progress: number;
    not_started: number;
}

interface CompletionChartProps {
    data: CompletionDataItem[];
    height?: number;
    layout?: 'horizontal' | 'vertical';
    showLegend?: boolean;
    className?: string;
}

/**
 * Pre-configured stacked bar chart for assessment completion status breakdown.
 *
 * Displays graded/submitted/in_progress/not_started as stacked segments.
 *
 * @example
 * <CompletionChart
 *   data={[
 *     { name: 'Exam 1', graded: 20, submitted: 3, in_progress: 2, not_started: 5 },
 *     { name: 'Exam 2', graded: 15, submitted: 5, in_progress: 4, not_started: 6 },
 *   ]}
 * />
 */
export default function CompletionChart({
    data,
    height = 250,
    layout = 'horizontal',
    showLegend = true,
    className = '',
}: CompletionChartProps) {
    const { t } = useTranslations();
    const isVertical = layout === 'vertical';

    const statusLabels: Record<string, string> = {
        graded: t('charts.completion.graded'),
        submitted: t('charts.completion.submitted'),
        in_progress: t('charts.completion.in_progress'),
        not_started: t('charts.completion.not_started'),
    };

    const statuses = ['graded', 'submitted', 'in_progress', 'not_started'] as const;

    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height={height}>
                <BarChart
                    data={data}
                    layout={isVertical ? 'vertical' : 'horizontal'}
                    margin={CHART_DEFAULTS.margin}
                >
                    <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />

                    {isVertical ? (
                        <>
                            <XAxis type="number" fontSize={CHART_DEFAULTS.fontSize} />
                            <YAxis
                                type="category"
                                dataKey="name"
                                fontSize={CHART_DEFAULTS.fontSize}
                                width={120}
                                tick={{ fontSize: 11 }}
                            />
                        </>
                    ) : (
                        <>
                            <XAxis
                                dataKey="name"
                                fontSize={CHART_DEFAULTS.fontSize}
                                tickLine={false}
                                axisLine={false}
                                tick={{ fontSize: 11 }}
                            />
                            <YAxis
                                fontSize={CHART_DEFAULTS.fontSize}
                                tickLine={false}
                                axisLine={false}
                                allowDecimals={false}
                            />
                        </>
                    )}

                    <Tooltip
                        contentStyle={CHART_DEFAULTS.tooltipStyle}
                        formatter={(value: number | undefined, name: string | undefined) => [
                            value ?? 0,
                            statusLabels[name ?? ''] ?? name,
                        ]}
                    />

                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize }}
                            formatter={(value: string) => statusLabels[value] ?? value}
                        />
                    )}

                    {statuses.map((status) => (
                        <Bar
                            key={status}
                            dataKey={status}
                            stackId="completion"
                            fill={COMPLETION_STATUS_COLORS[status]}
                            animationDuration={CHART_DEFAULTS.animationDuration}
                        />
                    ))}
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { CompletionChartProps, CompletionDataItem };
