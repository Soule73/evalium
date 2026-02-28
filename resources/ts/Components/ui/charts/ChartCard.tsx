import { type ReactNode } from 'react';

interface ChartCardProps {
    title: string;
    subtitle?: string;
    children: ReactNode;
    loading?: boolean;
    empty?: boolean;
    emptyMessage?: string;
    className?: string;
    height?: number;
    actions?: ReactNode;
}

/**
 * Card wrapper for chart components with title, loading skeleton, and empty state.
 */
export default function ChartCard({
    title,
    subtitle,
    children,
    loading = false,
    empty = false,
    emptyMessage,
    className = '',
    actions,
}: ChartCardProps) {
    return (
        <div className={`rounded-xl border border-gray-200 bg-white p-5 ${className}`}>
            <div className="mb-4 flex items-start justify-between">
                <div>
                    <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
                    {subtitle && <p className="mt-0.5 text-xs text-gray-500">{subtitle}</p>}
                </div>
                {actions && <div className="shrink-0">{actions}</div>}
            </div>

            {loading ? <ChartSkeleton /> : empty ? <ChartEmpty message={emptyMessage} /> : children}
        </div>
    );
}

function ChartSkeleton() {
    return (
        <div className="flex h-52 items-end gap-3 px-4 animate-pulse">
            <div className="h-[40%] w-full rounded-t bg-gray-100" />
            <div className="h-[65%] w-full rounded-t bg-gray-100" />
            <div className="h-[50%] w-full rounded-t bg-gray-100" />
            <div className="h-[80%] w-full rounded-t bg-gray-100" />
            <div className="h-[35%] w-full rounded-t bg-gray-100" />
            <div className="h-[55%] w-full rounded-t bg-gray-100" />
        </div>
    );
}

function ChartEmpty({ message }: { message?: string }) {
    return (
        <div className="flex h-52 items-center justify-center text-sm text-gray-400">
            {message || 'No data available'}
        </div>
    );
}

export { ChartSkeleton, ChartEmpty };
export type { ChartCardProps };
