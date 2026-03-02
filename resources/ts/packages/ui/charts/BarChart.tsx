import {
    BarChart as RechartsBarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { CHART_ANIMATION, CHART_COLORS, CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';
import CustomTooltip from './CustomTooltip';

interface BarChartDataItem {
    name: string;
    value?: number;
    [key: string]: string | number | undefined;
}

interface BarSeries {
    dataKey: string;
    name?: string;
    color?: string;
    stackId?: string;
}

interface BarChartProps {
    data: BarChartDataItem[];
    series?: BarSeries[];
    height?: number;
    layout?: 'horizontal' | 'vertical';
    showGrid?: boolean;
    showLegend?: boolean;
    showTooltip?: boolean;
    colorByItem?: boolean;
    colors?: string[];
    barSize?: number;
    xAxisLabel?: string;
    yAxisLabel?: string;
    formatTooltipValue?: (value: number) => string;
    className?: string;
}

/**
 * Reusable bar chart component supporting single/multi series, stacked, and horizontal layouts.
 *
 * @example
 * // Simple single-series
 * <BarChart data={[{ name: 'Math', value: 14 }, { name: 'Physics', value: 12 }]} />
 *
 * @example
 * // Multi-series stacked
 * <BarChart
 *   data={data}
 *   series={[
 *     { dataKey: 'graded', name: 'Graded', color: '#10b981', stackId: 'a' },
 *     { dataKey: 'pending', name: 'Pending', color: '#f59e0b', stackId: 'a' },
 *   ]}
 * />
 */
export default function BarChart({
    data,
    series,
    height = 250,
    layout = 'horizontal',
    showGrid = true,
    showLegend = false,
    showTooltip = true,
    colorByItem = false,
    colors = [...CHART_PALETTE],
    barSize,
    formatTooltipValue,
    className = '',
}: BarChartProps) {
    const isVertical = layout === 'vertical';
    const resolvedSeries = series ?? [{ dataKey: 'value', color: CHART_COLORS.primary }];

    const chartData = colorByItem
        ? data.map((item, idx) => ({ ...item, fill: colors[idx % colors.length] }))
        : data;

    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsBarChart
                    data={chartData}
                    layout={isVertical ? 'vertical' : 'horizontal'}
                    margin={CHART_DEFAULTS.margin}
                    barCategoryGap="20%"
                >
                    {showGrid && (
                        <CartesianGrid
                            strokeDasharray="3 3"
                            stroke={CHART_DEFAULTS.gridStroke}
                            vertical={false}
                        />
                    )}

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
                                fontSize={CHART_DEFAULTS.fontSize}
                                width={100}
                                tickLine={false}
                                tick={CHART_DEFAULTS.axisStyle.tick}
                            />
                        </>
                    ) : (
                        <>
                            <XAxis
                                dataKey="name"
                                fontSize={CHART_DEFAULTS.fontSize}
                                {...CHART_DEFAULTS.axisStyle}
                            />
                            <YAxis
                                fontSize={CHART_DEFAULTS.fontSize}
                                {...CHART_DEFAULTS.axisStyle}
                            />
                        </>
                    )}

                    {showTooltip && (
                        <Tooltip
                            content={<CustomTooltip formatValue={formatTooltipValue} />}
                            cursor={{ fill: 'rgba(79, 70, 229, 0.04)', radius: 4 }}
                        />
                    )}

                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize, paddingTop: 12 }}
                            iconType="circle"
                            iconSize={8}
                        />
                    )}

                    {resolvedSeries.map((s, idx) => (
                        <Bar
                            key={s.dataKey}
                            dataKey={s.dataKey}
                            name={s.name}
                            fill={s.color ?? colors[idx % colors.length]}
                            stackId={s.stackId}
                            barSize={barSize}
                            radius={CHART_DEFAULTS.barRadius}
                            animationDuration={CHART_ANIMATION.duration}
                            animationEasing={CHART_ANIMATION.easing}
                            animationBegin={idx * CHART_ANIMATION.delayPerSeries}
                        />
                    ))}
                </RechartsBarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { BarChartProps, BarChartDataItem, BarSeries };
