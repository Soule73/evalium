import { useMemo } from 'react';
import { Badge } from '@/Components';
import { BaseEntityList } from './BaseEntityList';
import { type EntityListConfig } from './types/listConfig';
import { type ClassSubject } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks';

interface ClassSubjectHistoryListProps {
    data: PaginationType<ClassSubject>;
}

/**
 * List component for displaying class-subject assignment history (past teachers).
 */
export function ClassSubjectHistoryList({ data }: ClassSubjectHistoryListProps) {
    const { t } = useTranslations();

    const config: EntityListConfig<ClassSubject> = useMemo(
        () => ({
            entity: 'class-subject-history',
            columns: [
                {
                    key: 'teacher',
                    labelKey: 'admin_pages.class_subjects.teacher',
                    render: (item) => (
                        <div>
                            <div className="text-sm font-medium text-gray-900">
                                {item.teacher?.name || '-'}
                            </div>
                            {item.teacher?.email && (
                                <div className="text-xs text-gray-500">{item.teacher.email}</div>
                            )}
                        </div>
                    ),
                },
                {
                    key: 'period',
                    labelKey: 'admin_pages.class_subjects.validity_period',
                    render: (item) => (
                        <div className="text-sm text-gray-600">
                            {formatDate(item.valid_from)}
                            {item.valid_to && ` - ${formatDate(item.valid_to)}`}
                        </div>
                    ),
                },
                {
                    key: 'coefficient',
                    labelKey: 'admin_pages.class_subjects.coefficient',
                    render: (item) => (
                        <Badge label={item.coefficient.toString()} type="info" size="sm" />
                    ),
                },
                {
                    key: 'semester',
                    labelKey: 'admin_pages.class_subjects.semester',
                    render: (item) => (
                        <div className="text-sm text-gray-600">
                            {item.semester
                                ? `S${item.semester.order_number}`
                                : t('admin_pages.class_subjects.all_year')}
                        </div>
                    ),
                },
                {
                    key: 'status',
                    labelKey: 'admin_pages.common.status',
                    render: (item) => {
                        const isActive = !item.valid_to;
                        return (
                            <Badge
                                label={
                                    isActive
                                        ? t('admin_pages.class_subjects.current')
                                        : t('admin_pages.class_subjects.past')
                                }
                                type={isActive ? 'success' : 'gray'}
                                size="sm"
                            />
                        );
                    },
                },
            ],
            actions: [],
        }),
        [t],
    );

    return <BaseEntityList data={data} config={config} variant="admin" />;
}
