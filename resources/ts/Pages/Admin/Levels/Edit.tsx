import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { breadcrumbs, trans } from '@/utils';
import { type Level } from '@/types';
import { LevelForm } from '@/Components/features/levels';

interface Props {
    level: Level;
}

export default function EditLevel({ level }: Props) {
    const handleCancel = () => {
        window.history.back();
    };

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.levels.edit')}
            breadcrumb={breadcrumbs.levelEdit(level.name)}
        >
            <LevelForm
                title={trans('admin_pages.levels.edit_title')}
                subtitle={trans('admin_pages.levels.edit_subtitle')}
                level={level}
                onCancel={handleCancel}
            />
        </AuthenticatedLayout>
    );
}
