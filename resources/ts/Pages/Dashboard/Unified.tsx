import React from 'react';
import { usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, User } from '@/types';
import Section from '@/Components/Section';

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
        <AuthenticatedLayout title={"ableau de bord"}>
            <Section title="Mon compte">
                <div className="bg-gray-50 p-6 rounded-lg">
                    <dl className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Nom</dt>
                            <dd className="mt-1 text-sm text-gray-900">{user.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Email</dt>
                            <dd className="mt-1 text-sm text-gray-900">{user.email}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Permissions</dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {auth.permissions.length} permissions actives
                            </dd>
                        </div>
                    </dl>
                </div>
            </Section>
        </AuthenticatedLayout>
    );
};




export default UnifiedDashboard;
