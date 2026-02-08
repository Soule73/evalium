import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { breadcrumbs, trans } from '@/utils';
import { LevelForm } from '@/Components/features/levels';

export default function CreateLevel() {
    const handleCancel = () => {
        window.history.back();
    };

    return (
        <AuthenticatedLayout breadcrumb={breadcrumbs.levelCreate()}>
            <LevelForm
                title={trans('admin_pages.levels.create_title')}
                subtitle={trans('admin_pages.levels.create_subtitle')}
                onCancel={handleCancel}
            />
        </AuthenticatedLayout>
    );
}

