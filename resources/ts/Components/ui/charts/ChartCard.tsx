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
        <div className={`rounded-xl border border-gray-100 bg-white p-5 duration-200 ${className}`}>
            <div className="mb-4 flex items-start justify-between">
                <div>
                    <h3 className="text-sm font-semibold text-gray-900">{title}</h3>
                    {subtitle && <p className="mt-0.5 text-xs text-gray-500">{subtitle}</p>}
                </div>
                {actions && <div className="shrink-0">{actions}</div>}
            </div>

            {loading ? (
                <ChartSkeleton />
            ) : empty ? (
                <ChartEmpty message={emptyMessage} />
            ) : (
                <div className="animate-chart-fade-in">{children}</div>
            )}
        </div>
    );
}

function ChartSkeleton() {
    return (
        <div className="flex h-52 items-end gap-3 px-4">
            {[40, 65, 50, 80, 35, 55, 70, 45].map((h, i) => (
                <div
                    key={i}
                    className="w-full rounded-t-md bg-linear-to-t from-gray-100 to-gray-50"
                    style={{
                        height: `${h}%`,
                        animation: `pulse 1.5s ease-in-out ${i * 0.1}s infinite`,
                    }}
                />
            ))}
        </div>
    );
}

function ChartEmpty({ message }: { message?: string }) {
    return (
        <div className="flex h-52 flex-col items-center justify-center gap-2">
            <svg
                className="h-10 w-10 text-gray-200"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={1.5}
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
                />
            </svg>
            <span className="text-sm text-gray-400">{message || 'No data available'}</span>
        </div>
    );
}

export { ChartSkeleton, ChartEmpty };
export type { ChartCardProps };
