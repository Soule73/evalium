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
import { CHART_ANIMATION, CHART_COLORS, CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';
import CustomTooltip from './CustomTooltip';

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
                <RechartsLineChart data={data} margin={{ ...CHART_DEFAULTS.margin, bottom: 12 }}>
                    {showGrid && (
                        <CartesianGrid
                            strokeDasharray="3 3"
                            stroke={CHART_DEFAULTS.gridStroke}
                            vertical={false}
                        />
                    )}

                    <XAxis
                        dataKey="name"
                        fontSize={CHART_DEFAULTS.fontSize}
                        {...CHART_DEFAULTS.axisStyle}
                        dy={8}
                    />
                    <YAxis
                        fontSize={CHART_DEFAULTS.fontSize}
                        {...CHART_DEFAULTS.axisStyle}
                        domain={yDomain}
                        width={35}
                    />

                    {showTooltip && (
                        <Tooltip content={<CustomTooltip formatValue={formatTooltipValue} />} />
                    )}

                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize, paddingTop: 12 }}
                            iconType="circle"
                            iconSize={8}
                        />
                    )}

                    {resolvedSeries.map((s, idx) => {
                        const color = s.color ?? colors[idx % colors.length];
                        return (
                            <Line
                                key={s.dataKey}
                                type={curved ? 'monotone' : 'linear'}
                                dataKey={s.dataKey}
                                name={s.name}
                                stroke={color}
                                strokeWidth={2.5}
                                strokeDasharray={s.dashed ? '6 4' : undefined}
                                dot={
                                    showDots
                                        ? {
                                              r: 4,
                                              strokeWidth: 2,
                                              fill: '#fff',
                                              stroke: color,
                                          }
                                        : false
                                }
                                activeDot={{
                                    r: 6,
                                    strokeWidth: 2,
                                    fill: '#fff',
                                    stroke: color,
                                }}
                                animationDuration={CHART_ANIMATION.duration}
                                animationEasing={CHART_ANIMATION.easing}
                                animationBegin={idx * CHART_ANIMATION.delayPerSeries}
                            />
                        );
                    })}
                </RechartsLineChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { LineChartProps, LineChartDataItem, LineSeries };
