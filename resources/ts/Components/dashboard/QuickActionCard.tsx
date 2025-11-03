import React from 'react';
import { Link } from '@inertiajs/react';

interface QuickActionCardProps {
    title: string;
    description: string;
    href: string;
    IconComponent: React.ComponentType<{ className?: string }>;
}

export default function QuickActionCard({ title, description, href, IconComponent }: QuickActionCardProps) {
    return (
        <Link
            href={href}
            className="block bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-primary-500 hover:shadow-md transition-all duration-200"
        >
            <div className="flex items-start gap-4">
                <div className="p-3 bg-primary-100 text-primary-600 rounded-lg">
                    <IconComponent className="w-6 h-6" />
                </div>
                <div className="flex-1">
                    <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                    <p className="mt-1 text-sm text-gray-600">{description}</p>
                </div>
            </div>
        </Link>
    );
}
