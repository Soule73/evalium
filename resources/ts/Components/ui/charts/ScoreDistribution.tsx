import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Cell,
    LabelList,
} from 'recharts';
import { CHART_ANIMATION, CHART_DEFAULTS, SCORE_RANGE_COLORS, SCORE_RANGES } from './chartTheme';

interface ScoreDistributionItem {
    range: string;
    count: number;
}

interface ScoreDistributionProps {
    data: ScoreDistributionItem[];
    height?: number;
    showGrid?: boolean;
    showValueLabels?: boolean;
    className?: string;
}

/**
 * Pre-configured bar chart for score distribution across the 0-20 grading scale.
 *
 * Uses fixed score ranges (0-4, 5-8, 9-12, 13-16, 17-20) with semantic colors:
 * red for low, yellow for below average, blue for average, green for good, indigo for excellent.
 *
 * @example
 * <ScoreDistribution
 *   data={[
 *     { range: '0-4', count: 2 },
 *     { range: '5-8', count: 5 },
 *     { range: '9-12', count: 12 },
 *     { range: '13-16', count: 8 },
 *     { range: '17-20', count: 3 },
 *   ]}
 * />
 */
export default function ScoreDistribution({
    data,
    height = 250,
    showGrid = true,
    showValueLabels = true,
    className = '',
}: ScoreDistributionProps) {
    const orderedData = SCORE_RANGES.map((range) => {
        const found = data.find((d) => d.range === range);
        return { range, count: found?.count ?? 0, fill: SCORE_RANGE_COLORS[range] ?? '#6b7280' };
    });

    const hasData = orderedData.some((d) => d.count > 0);

    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height={height}>
                <BarChart
                    data={orderedData}
                    margin={{ ...CHART_DEFAULTS.margin, top: 20 }}
                    barCategoryGap="20%"
                >
                    {showGrid && (
                        <CartesianGrid
                            strokeDasharray="3 3"
                            stroke={CHART_DEFAULTS.gridStroke}
                            vertical={false}
                        />
                    )}

                    <XAxis
                        dataKey="range"
                        fontSize={CHART_DEFAULTS.fontSize}
                        {...CHART_DEFAULTS.axisStyle}
                    />
                    <YAxis
                        fontSize={CHART_DEFAULTS.fontSize}
                        {...CHART_DEFAULTS.axisStyle}
                        allowDecimals={false}
                        width={30}
                    />

                    <Tooltip
                        contentStyle={CHART_DEFAULTS.tooltipStyle}
                        formatter={(value: number | undefined) => [`${value ?? 0}`, 'Students']}
                        labelFormatter={(label) => `Score: ${String(label)} / 20`}
                        cursor={{ fill: 'rgba(0,0,0,0.03)', radius: 4 }}
                    />

                    <Bar
                        dataKey="count"
                        radius={CHART_DEFAULTS.barRadius}
                        animationDuration={CHART_ANIMATION.duration}
                        animationEasing={CHART_ANIMATION.easing}
                    >
                        {orderedData.map((entry) => (
                            <Cell key={entry.range} fill={entry.fill} />
                        ))}
                        {showValueLabels && hasData && (
                            <LabelList
                                dataKey="count"
                                position="top"
                                fontSize={11}
                                fontWeight={600}
                                fill="#6b7280"
                                formatter={(v: unknown) => {
                                    const n = Number(v);
                                    return n > 0 ? String(n) : '';
                                }}
                            />
                        )}
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { ScoreDistributionProps, ScoreDistributionItem };
