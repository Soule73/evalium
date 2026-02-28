import { PieChart, Pie, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import type { PieLabelRenderProps } from 'recharts';
import { CHART_DEFAULTS, CHART_PALETTE } from './chartTheme';

interface DonutChartDataItem {
    name: string;
    value: number;
    color?: string;
}

interface DonutChartProps {
    data: DonutChartDataItem[];
    height?: number;
    innerRadius?: number;
    outerRadius?: number;
    showLegend?: boolean;
    showTooltip?: boolean;
    showLabels?: boolean;
    colors?: string[];
    centerLabel?: string;
    centerValue?: string | number;
    formatTooltipValue?: (value: number) => string;
    className?: string;
}

/**
 * Reusable donut/pie chart component with optional center label.
 *
 * @example
 * <DonutChart
 *   data={[
 *     { name: 'Students', value: 120, color: '#10b981' },
 *     { name: 'Teachers', value: 15, color: '#3b82f6' },
 *   ]}
 *   centerLabel="Total"
 *   centerValue={135}
 * />
 */
export default function DonutChart({
    data,
    height = 250,
    innerRadius = 60,
    outerRadius = 90,
    showLegend = true,
    showTooltip = true,
    showLabels = false,
    colors = [...CHART_PALETTE],
    centerLabel,
    centerValue,
    formatTooltipValue,
    className = '',
}: DonutChartProps) {
    const total = data.reduce((sum, d) => sum + d.value, 0);

    const coloredData = data.map((entry, idx) => ({
        ...entry,
        fill: entry.color ?? colors[idx % colors.length],
    }));

    const renderCustomLabel = (props: PieLabelRenderProps) => {
        const {
            cx,
            cy,
            midAngle,
            innerRadius: ir,
            outerRadius: or,
            percent,
        } = props as {
            cx: number;
            cy: number;
            midAngle: number;
            innerRadius: number;
            outerRadius: number;
            percent: number;
        };

        const RADIAN = Math.PI / 180;
        const radius = ir + (or - ir) * 0.5;
        const angle = midAngle ?? 0;
        const x = cx + radius * Math.cos(-angle * RADIAN);
        const y = cy + radius * Math.sin(-angle * RADIAN);

        if (percent < 0.05) return null;

        return (
            <text
                x={x}
                y={y}
                fill="white"
                textAnchor="middle"
                dominantBaseline="central"
                fontSize={CHART_DEFAULTS.fontSize}
                fontWeight={600}
            >
                {`${(percent * 100).toFixed(0)}%`}
            </text>
        );
    };

    return (
        <div className={className}>
            <ResponsiveContainer width="100%" height={height}>
                <PieChart>
                    <Pie
                        data={coloredData}
                        cx="50%"
                        cy="50%"
                        innerRadius={innerRadius}
                        outerRadius={outerRadius}
                        dataKey="value"
                        animationDuration={CHART_DEFAULTS.animationDuration}
                        label={showLabels ? renderCustomLabel : undefined}
                        labelLine={false}
                        stroke="white"
                        strokeWidth={2}
                    />

                    {showTooltip && (
                        <Tooltip
                            contentStyle={CHART_DEFAULTS.tooltipStyle}
                            formatter={
                                formatTooltipValue
                                    ? (value: number | undefined) => formatTooltipValue(value ?? 0)
                                    : (value: number | undefined) => {
                                          const v = value ?? 0;
                                          return [
                                              `${v} (${total > 0 ? ((v / total) * 100).toFixed(1) : 0}%)`,
                                          ];
                                      }
                            }
                        />
                    )}

                    {showLegend && (
                        <Legend
                            wrapperStyle={{ fontSize: CHART_DEFAULTS.fontSize }}
                            iconType="circle"
                            iconSize={8}
                        />
                    )}

                    {centerLabel !== undefined && (
                        <text
                            x="50%"
                            y="46%"
                            textAnchor="middle"
                            dominantBaseline="central"
                            className="fill-gray-400"
                            fontSize={11}
                        >
                            {centerLabel}
                        </text>
                    )}
                    {centerValue !== undefined && (
                        <text
                            x="50%"
                            y="55%"
                            textAnchor="middle"
                            dominantBaseline="central"
                            className="fill-gray-900"
                            fontSize={20}
                            fontWeight={700}
                        >
                            {centerValue}
                        </text>
                    )}
                </PieChart>
            </ResponsiveContainer>
        </div>
    );
}

export type { DonutChartProps, DonutChartDataItem };
