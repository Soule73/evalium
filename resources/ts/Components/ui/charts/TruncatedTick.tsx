interface TruncatedTickProps {
    x?: number;
    y?: number;
    payload?: { value: string };
    maxLength?: number;
    fontSize?: number;
    fill?: string;
    textAnchor?: 'start' | 'middle' | 'end';
    dy?: number;
    dx?: number;
}

/**
 * Custom Recharts tick component that truncates long labels and shows full text on hover via SVG title.
 *
 * Intended for non-rotated axes only (e.g., vertical bar chart YAxis).
 * For rotated labels, use native Recharts angle + tickFormatter props instead.
 */
export default function TruncatedTick({
    x = 0,
    y = 0,
    payload,
    maxLength = 20,
    fontSize = 11,
    fill = '#6b7280',
    textAnchor = 'end',
    dy = 0,
    dx = 0,
}: TruncatedTickProps) {
    const value = payload?.value ?? '';
    const truncated = value.length > maxLength ? value.slice(0, maxLength) + '...' : value;

    return (
        <g transform={`translate(${x},${y})`}>
            <text x={dx} y={dy} textAnchor={textAnchor} fill={fill} fontSize={fontSize}>
                {truncated}
                <title>{value}</title>
            </text>
        </g>
    );
}

export type { TruncatedTickProps };
