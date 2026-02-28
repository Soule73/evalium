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
import { CHART_ANIMATION, CHART_COLORS, CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';

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
                <RechartsRadarChart cx="50%" cy="50%" outerRadius="72%" data={data}>
                    <PolarGrid stroke="#e5e7eb" strokeDasharray="3 3" />
                    <PolarAngleAxis
                        dataKey="subject"
                        fontSize={11}
                        tick={({ x, y, payload, textAnchor }) => (
                            <text
                                x={x}
                                y={y}
                                textAnchor={textAnchor}
                                fill="#6b7280"
                                fontSize={11}
                                fontWeight={500}
                            >
                                {String(payload.value).length > 12
                                    ? `${String(payload.value).slice(0, 12)}...`
                                    : String(payload.value)}
                            </text>
                        )}
                    />
                    <PolarRadiusAxis
                        angle={90}
                        domain={[0, maxValue]}
                        fontSize={10}
                        tick={{ fill: '#d1d5db', fontSize: 10 }}
                        axisLine={false}
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
                                dot={{ r: 3, fill: color, strokeWidth: 0 }}
                                animationDuration={CHART_ANIMATION.duration}
                                animationEasing={CHART_ANIMATION.easing}
                                animationBegin={idx * CHART_ANIMATION.delayPerSeries}
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

                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize, paddingTop: 8 }}
                            iconType="circle"
                            iconSize={8}
                        />
                    )}
                </RechartsRadarChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { RadarChartProps, RadarChartDataItem, RadarSeries };
