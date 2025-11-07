import { createElement } from "react";

interface StatCardProps {
    title: string;
    value: string | number;
    icon: React.FC<React.SVGProps<SVGSVGElement>>;
    color: 'blue' | 'green' | 'purple' | 'red' | 'yellow';
    className?: string;
}
const StatCard: React.FC<StatCardProps> = ({ title, value, icon, color, className }) => {
    const colorClasses = {
        blue: { bg: 'bg-blue-100', text: 'text-blue-900', border: 'border-blue-200', iconBg: 'bg-blue-200' },
        green: { bg: 'bg-green-100', text: 'text-green-900', border: 'border-green-200', iconBg: 'bg-green-200' },
        purple: { bg: 'bg-purple-100', text: 'text-purple-900', border: 'border-purple-200', iconBg: 'bg-purple-200' },
        red: { bg: 'bg-red-100', text: 'text-red-900', border: 'border-red-200', iconBg: 'bg-red-200' },
        yellow: { bg: 'bg-yellow-100', text: 'text-yellow-900', border: 'border-yellow-200', iconBg: 'bg-yellow-200' },
    };
    return (
        <div className={`border rounded-lg p-6 ${colorClasses[color].border} ${colorClasses[color].bg} ${className}`}>
            <div className="flex items-center justify-between">
                <div>
                    <p className={`text-sm font-medium ${colorClasses[color].text}`}>
                        {title}
                    </p>
                    <p className={`text-3xl font-bold ${colorClasses[color].text}`}>
                        {value}
                    </p>
                </div>
                <div className={`p-3 rounded-full  ${colorClasses[color].iconBg}`}>
                    {createElement(icon, { className: ` w-8 h-8 ${colorClasses[color].text} ` })}
                </div>
            </div>
        </div>
    );
};

export default StatCard;