import type { Payload } from 'recharts/types/component/DefaultTooltipContent';
import { CHART_DEFAULTS } from './chartTheme';

export interface CustomTooltipProps {
    active?: boolean;
    payload?: Payload<number, string>[];
    label?: string;
    formatValue?: (value: number) => string;
    unit?: string;
}

/**
 * Shared custom tooltip component for all chart types.
 *
 * Renders a polished floating card with label and color-coded values.
 */
export default function CustomTooltip({
    active,
    payload,
    label,
    formatValue,
    unit,
}: CustomTooltipProps) {
    if (!active || !payload?.length) return null;

    return (
        <div
            className="animate-chart-fade-in"
            style={{
                ...CHART_DEFAULTS.tooltipStyle,
                border: `1px solid ${CHART_DEFAULTS.tooltipStyle.borderColor}`,
            }}
        >
            {label && (
                <p
                    style={{
                        margin: '0 0 6px 0',
                        fontWeight: 600,
                        fontSize: 13,
                        color: '#374151',
                    }}
                >
                    {label}
                </p>
            )}
            {payload.map((entry, idx) => {
                const value = entry.value ?? 0;
                const displayValue = formatValue ? formatValue(value) : `${value}${unit ?? ''}`;
                return (
                    <div
                        key={idx}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                            fontSize: 12,
                            color: '#6b7280',
                            marginBottom: idx < payload.length - 1 ? 3 : 0,
                        }}
                    >
                        <span
                            style={{
                                width: 8,
                                height: 8,
                                borderRadius: '50%',
                                backgroundColor: entry.color ?? '#4f46e5',
                                flexShrink: 0,
                            }}
                        />
                        <span style={{ flex: 1 }}>{entry.name ?? String(entry.dataKey ?? '')}</span>
                        <span style={{ fontWeight: 600, color: '#111827' }}>{displayValue}</span>
                    </div>
                );
            })}
        </div>
    );
}
