import React from 'react';
import { usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PageProps, User } from '@/types';
import { Section } from '@/Components';
import { breadcrumbs, trans } from '@/utils';

interface UnifiedDashboardProps extends PageProps {
    user: User;
}

/**
 * Dashboard default
 */
const UnifiedDashboard: React.FC<UnifiedDashboardProps> = ({
    user,
}) => {
    const { auth } = usePage<PageProps>().props;

    return (
        <AuthenticatedLayout title={trans('dashboard.title.unified')}
            breadcrumb={breadcrumbs.dashboard()}>
            <Section title={trans('dashboard.unified.my_account')}>
                <div className="bg-gray-50 p-6 rounded-lg" data-e2e='dashboard-content'>
                    <dl className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">{trans('dashboard.unified.name')}</dt>
                            <dd className="mt-1 text-sm text-gray-900">{user.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">{trans('dashboard.unified.email')}</dt>
                            <dd className="mt-1 text-sm text-gray-900">{user.email}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">{trans('dashboard.unified.permissions')}</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {trans('dashboard.unified.active_permissions', { count: auth.permissions.length })}
                            </dd>
                        </div>
                    </dl>
                </div>
            </Section>
        </AuthenticatedLayout>
    );
};




export default UnifiedDashboard;
