import {
    LineChart as RechartsLineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { CHART_COLORS, CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';

interface LineChartDataItem {
    name: string;
    [key: string]: string | number | null | undefined;
}

interface LineSeries {
    dataKey: string;
    name?: string;
    color?: string;
    dashed?: boolean;
}

interface LineChartProps {
    data: LineChartDataItem[];
    series?: LineSeries[];
    height?: number;
    showGrid?: boolean;
    showLegend?: boolean;
    showTooltip?: boolean;
    showDots?: boolean;
    curved?: boolean;
    colors?: string[];
    yDomain?: [number, number];
    formatTooltipValue?: (value: number) => string;
    className?: string;
}

/**
 * Reusable line chart component for trends and time series data.
 *
 * @example
 * <LineChart
 *   data={[
 *     { name: 'Jan', average: 12.5 },
 *     { name: 'Feb', average: 14.2 },
 *   ]}
 *   series={[{ dataKey: 'average', name: 'Class Average', color: '#4f46e5' }]}
 * />
 */
export default function LineChart({
    data,
    series,
    height = 250,
    showGrid = true,
    showLegend = false,
    showTooltip = true,
    showDots = true,
    curved = true,
    colors = [...CHART_PALETTE],
    yDomain,
    formatTooltipValue,
    className = '',
}: LineChartProps) {
    const resolvedSeries = series ?? [{ dataKey: 'value', color: CHART_COLORS.primary }];

    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsLineChart data={data} margin={CHART_DEFAULTS.margin}>
                    {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#f3f4f6" />}

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
                        domain={yDomain}
                    />

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
                        <Line
                            key={s.dataKey}
                            type={curved ? 'monotone' : 'linear'}
                            dataKey={s.dataKey}
                            name={s.name}
                            stroke={s.color ?? colors[idx % colors.length]}
                            strokeWidth={2}
                            strokeDasharray={s.dashed ? '5 5' : undefined}
                            dot={showDots ? { r: 4, strokeWidth: 2 } : false}
                            activeDot={{ r: 6 }}
                            animationDuration={CHART_DEFAULTS.animationDuration}
                        />
                    ))}
                </RechartsLineChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { LineChartProps, LineChartDataItem, LineSeries };
