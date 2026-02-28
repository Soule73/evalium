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
import { CHART_ANIMATION, CHART_DEFAULTS, COMPLETION_STATUS_COLORS } from './chartTheme';
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
                    barCategoryGap="25%"
                >
                    <CartesianGrid
                        strokeDasharray="3 3"
                        stroke={CHART_DEFAULTS.gridStroke}
                        horizontal={!isVertical}
                        vertical={isVertical}
                    />

                    {isVertical ? (
                        <>
                            <XAxis
                                type="number"
                                fontSize={CHART_DEFAULTS.fontSize}
                                {...CHART_DEFAULTS.axisStyle}
                            />
                            <YAxis
                                type="category"
                                dataKey="name"
                                fontSize={11}
                                width={120}
                                tickLine={false}
                                tick={{ fill: '#6b7280', fontSize: 11 }}
                            />
                        </>
                    ) : (
                        <>
                            <XAxis
                                dataKey="name"
                                fontSize={CHART_DEFAULTS.fontSize}
                                {...CHART_DEFAULTS.axisStyle}
                                tick={{ fill: '#6b7280', fontSize: 11 }}
                            />
                            <YAxis
                                fontSize={CHART_DEFAULTS.fontSize}
                                {...CHART_DEFAULTS.axisStyle}
                                allowDecimals={false}
                            />
                        </>
                    )}

                    <Tooltip
                        contentStyle={CHART_DEFAULTS.tooltipStyle}
                        formatter={(value: number | undefined, name: string | undefined) => {
                            const total = data[0]
                                ? data[0].graded +
                                  data[0].submitted +
                                  data[0].in_progress +
                                  data[0].not_started
                                : 0;
                            const v = value ?? 0;
                            const pct = total > 0 ? ((v / total) * 100).toFixed(0) : 0;
                            return [`${v} (${pct}%)`, statusLabels[name ?? ''] ?? name];
                        }}
                    />

                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize, paddingTop: 8 }}
                            iconType="circle"
                            iconSize={8}
                            formatter={(value: string) => statusLabels[value] ?? value}
                        />
                    )}

                    {statuses.map((status, idx) => (
                        <Bar
                            key={status}
                            dataKey={status}
                            stackId="completion"
                            fill={COMPLETION_STATUS_COLORS[status]}
                            radius={status === 'not_started' ? CHART_DEFAULTS.barRadius : undefined}
                            animationDuration={CHART_ANIMATION.duration}
                            animationEasing={CHART_ANIMATION.easing}
                            animationBegin={idx * 80}
                        />
                    ))}
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { CompletionChartProps, CompletionDataItem };
