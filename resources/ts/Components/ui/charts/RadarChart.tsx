import {
    RadarChart as RechartsRadarChart,
    PolarGrid,
    PolarAngleAxis,
    PolarRadiusAxis,
    Radar,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { CHART_COLORS, CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';

interface RadarChartDataItem {
    subject: string;
    [key: string]: string | number | null | undefined;
}

interface RadarSeries {
    dataKey: string;
    name?: string;
    color?: string;
    fillOpacity?: number;
}

interface RadarChartProps {
    data: RadarChartDataItem[];
    series?: RadarSeries[];
    height?: number;
    maxValue?: number;
    showLegend?: boolean;
    showTooltip?: boolean;
    colors?: string[];
    formatTooltipValue?: (value: number) => string;
    className?: string;
}

/**
 * Reusable radar/spider chart for multi-dimensional data comparison.
 *
 * @example
 * <RadarChart
 *   data={[
 *     { subject: 'Math', grade: 15.5, classAverage: 12.3 },
 *     { subject: 'Physics', grade: 13.0, classAverage: 11.8 },
 *   ]}
 *   series={[
 *     { dataKey: 'grade', name: 'Your Grade' },
 *     { dataKey: 'classAverage', name: 'Class Average', color: '#6b7280' },
 *   ]}
 *   maxValue={20}
 * />
 */
export default function RadarChart({
    data,
    series,
    height = 300,
    maxValue = 20,
    showLegend = true,
    showTooltip = true,
    colors = [...CHART_PALETTE],
    formatTooltipValue,
    className = '',
}: RadarChartProps) {
    const resolvedSeries = series ?? [{ dataKey: 'value', color: CHART_COLORS.primary }];

    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height={height}>
                <RechartsRadarChart cx="50%" cy="50%" outerRadius="75%" data={data}>
                    <PolarGrid stroke="#e5e7eb" />
                    <PolarAngleAxis
                        dataKey="subject"
                        fontSize={CHART_DEFAULTS.fontSize}
                        tick={{ fill: '#6b7280' }}
                    />
                    <PolarRadiusAxis
                        angle={90}
                        domain={[0, maxValue]}
                        fontSize={10}
                        tick={{ fill: '#9ca3af' }}
                    />

                    {resolvedSeries.map((s, idx) => {
                        const color = s.color ?? colors[idx % colors.length];
                        return (
                            <Radar
                                key={s.dataKey}
                                name={s.name}
                                dataKey={s.dataKey}
                                stroke={color}
                                fill={color}
                                fillOpacity={s.fillOpacity ?? 0.15}
                                strokeWidth={2}
                                animationDuration={CHART_DEFAULTS.animationDuration}
                            />
                        );
                    })}

                    {showTooltip && (
                        <Tooltip
                            contentStyle={CHART_DEFAULTS.tooltipStyle}
                            formatter={
                                formatTooltipValue
                                    ? (value: number | undefined) => formatTooltipValue(value ?? 0)
                                    : (value: number | undefined) => `${value ?? 0} / ${maxValue}`
                            }
                        />
                    )}

                    {showLegend && <Legend wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize }} />}
                </RechartsRadarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { RadarChartProps, RadarChartDataItem, RadarSeries };
