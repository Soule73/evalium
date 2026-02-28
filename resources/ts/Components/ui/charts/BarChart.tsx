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
import { CHART_COLORS, CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';

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
                >
                    {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />}

                    {isVertical ? (
                        <>
                            <XAxis type="number" fontSize={CHART_DEFAULTS.fontSize} />
                            <YAxis
                                type="category"
                                dataKey="name"
                                fontSize={CHART_DEFAULTS.fontSize}
                                width={100}
                            />
                        </>
                    ) : (
                        <>
                            <XAxis
                                dataKey="name"
                                fontSize={CHART_DEFAULTS.fontSize}
                                tickLine={false}
                                axisLine={false}
                            />
                            <YAxis
                                fontSize={CHART_DEFAULTS.fontSize}
                                tickLine={false}
                                axisLine={false}
                            />
                        </>
                    )}

                    {showTooltip && (
                        <Tooltip
                            contentStyle={CHART_DEFAULTS.tooltipStyle}
                            formatter={
                                formatTooltipValue
                                    ? (value: number | undefined) => formatTooltipValue(value ?? 0)
                                    : undefined
                            }
                        />
                    )}

                    {showLegend && <Legend wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize }} />}

                    {resolvedSeries.map((s, idx) => (
                        <Bar
                            key={s.dataKey}
                            dataKey={s.dataKey}
                            name={s.name}
                            fill={s.color ?? colors[idx % colors.length]}
                            stackId={s.stackId}
                            barSize={barSize}
                            radius={[4, 4, 0, 0]}
                            animationDuration={CHART_DEFAULTS.animationDuration}
                        />
                    ))}
                </RechartsBarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { BarChartProps, BarChartDataItem, BarSeries };
